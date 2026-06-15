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

$stmt = $conn->prepare("
    SELECT
        c.id AS conversation_id,
        c.user1_id,
        c.user2_id,
        c.updated_at,
        u.id_user AS other_id,
        u.prenom,
        u.nom,
        u.avatar,
        m.content AS last_message,
        m.created_at AS last_message_at,
        m.sender_id AS last_sender_id,
        (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND sender_id != ? AND is_read = 0) AS unread_count
    FROM conversations c
    JOIN user u ON u.id_user = IF(c.user1_id = ?, c.user2_id, c.user1_id)
    LEFT JOIN messages m ON m.id = (
        SELECT id FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1
    )
    WHERE c.user1_id = ? OR c.user2_id = ?
    ORDER BY c.updated_at DESC
");
$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$conversations = [];
while ($row = $result->fetch_assoc()) {
    $conversations[] = [
        'conversation_id' => (int)$row['conversation_id'],
        'other_user' => [
            'id' => (int)$row['other_id'],
            'name' => $row['prenom'] . ' ' . $row['nom'],
            'avatar' => $row['avatar']
        ],
        'last_message' => $row['last_message'] ?: '',
        'last_message_at' => $row['last_message_at'] ?: $row['updated_at'],
        'last_sender_id' => (int)($row['last_sender_id'] ?: 0),
        'unread_count' => (int)$row['unread_count']
    ];
}

echo json_encode(['conversations' => $conversations]);
?>
