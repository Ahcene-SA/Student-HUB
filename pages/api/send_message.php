<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_user'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

require_once __DIR__ . '/../../includes/db_config.php';
$conn = get_db_connection();
if ($conn->connect_error) {
    echo json_encode(['error' => 'DB connection failed']);
    exit();
}

$user_id = (int) $_SESSION['id_user'];
$conversation_id = isset($_POST['conversation_id']) ? (int) $_POST['conversation_id'] : 0;
$receiver_id = isset($_POST['receiver_id']) ? (int) $_POST['receiver_id'] : 0;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

if (empty($content)) {
    echo json_encode(['error' => 'Message empty']);
    exit();
}

// If no conversation_id but receiver_id, create or find conversation
if ($conversation_id <= 0 && $receiver_id > 0) {
    $stmt = $conn->prepare("
        SELECT id FROM conversations
        WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)
    ");
    $stmt->bind_param("iiii", $user_id, $receiver_id, $receiver_id, $user_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    if ($existing) {
        $conversation_id = (int)$existing['id'];
    } else {
        $stmt = $conn->prepare("INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $receiver_id);
        $stmt->execute();
        $conversation_id = $conn->insert_id;
    }
}

if ($conversation_id <= 0) {
    echo json_encode(['error' => 'Invalid conversation']);
    exit();
}

// Verify user is part of conversation
$stmt = $conn->prepare("SELECT user1_id, user2_id FROM conversations WHERE id = ?");
$stmt->bind_param("i", $conversation_id);
$stmt->execute();
$conv = $stmt->get_result()->fetch_assoc();
if (!$conv || ($conv['user1_id'] != $user_id && $conv['user2_id'] != $user_id)) {
    echo json_encode(['error' => 'Access denied']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $conversation_id, $user_id, $content);
$stmt->execute();
$message_id = $conn->insert_id;

// Update conversation timestamp
$stmt = $conn->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $conversation_id);
$stmt->execute();

// Get inserted message
$stmt = $conn->prepare("
    SELECT m.id, m.sender_id, m.content, m.created_at, m.is_read,
           u.prenom, u.nom, u.avatar
    FROM messages m
    JOIN user u ON u.id_user = m.sender_id
    WHERE m.id = ?
");
$stmt->bind_param("i", $message_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

echo json_encode([
    'success' => true,
    'conversation_id' => $conversation_id,
    'message' => [
        'id' => (int)$row['id'],
        'sender_id' => (int)$row['sender_id'],
        'content' => $row['content'],
        'created_at' => $row['created_at'],
        'is_read' => (bool)$row['is_read'],
        'author_name' => $row['prenom'] . ' ' . $row['nom'],
        'author_avatar' => $row['avatar']
    ]
]);
?>
