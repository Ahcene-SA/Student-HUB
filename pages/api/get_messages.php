<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_user'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$conn = new mysqli("127.0.0.1", "root", "root", "studenthub", 8889);
if ($conn->connect_error) {
    echo json_encode(['error' => 'DB connection failed']);
    exit();
}

$user_id = (int) $_SESSION['id_user'];
$conversation_id = isset($_GET['conversation_id']) ? (int) $_GET['conversation_id'] : 0;
$after = isset($_GET['after']) ? (int) $_GET['after'] : 0; // message id

if ($conversation_id <= 0) {
    echo json_encode(['error' => 'Invalid conversation_id']);
    exit();
}

// Verify user is part of this conversation
$stmt = $conn->prepare("SELECT user1_id, user2_id FROM conversations WHERE id = ?");
$stmt->bind_param("i", $conversation_id);
$stmt->execute();
$conv = $stmt->get_result()->fetch_assoc();
if (!$conv || ($conv['user1_id'] != $user_id && $conv['user2_id'] != $user_id)) {
    echo json_encode(['error' => 'Access denied']);
    exit();
}

// Mark messages as read
$stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ? AND is_read = 0");
$stmt->bind_param("ii", $conversation_id, $user_id);
$stmt->execute();

// Fetch messages
if ($after > 0) {
    $stmt = $conn->prepare("
        SELECT m.id, m.sender_id, m.content, m.created_at, m.is_read,
               u.prenom, u.nom, u.avatar
        FROM messages m
        JOIN user u ON u.id_user = m.sender_id
        WHERE m.conversation_id = ? AND m.id > ?
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param("ii", $conversation_id, $after);
} else {
    $stmt = $conn->prepare("
        SELECT m.id, m.sender_id, m.content, m.created_at, m.is_read,
               u.prenom, u.nom, u.avatar
        FROM messages m
        JOIN user u ON u.id_user = m.sender_id
        WHERE m.conversation_id = ?
        ORDER BY m.created_at ASC
        LIMIT 100
    ");
    $stmt->bind_param("i", $conversation_id);
}
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'id' => (int)$row['id'],
        'sender_id' => (int)$row['sender_id'],
        'content' => $row['content'],
        'created_at' => $row['created_at'],
        'is_read' => (bool)$row['is_read'],
        'author_name' => $row['prenom'] . ' ' . $row['nom'],
        'author_avatar' => $row['avatar']
    ];
}

// Get other user info
$other_id = ($conv['user1_id'] == $user_id) ? $conv['user2_id'] : $conv['user1_id'];
$stmt = $conn->prepare("SELECT id_user, prenom, nom, avatar FROM user WHERE id_user = ?");
$stmt->bind_param("i", $other_id);
$stmt->execute();
$other = $stmt->get_result()->fetch_assoc();

echo json_encode([
    'messages' => $messages,
    'other_user' => [
        'id' => (int)$other['id_user'],
        'name' => $other['prenom'] . ' ' . $other['nom'],
        'avatar' => $other['avatar']
    ]
]);
?>
