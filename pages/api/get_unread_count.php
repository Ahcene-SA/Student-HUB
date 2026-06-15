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

$stmt = $conn->prepare("
    SELECT COUNT(*) FROM messages m
    JOIN conversations c ON c.id = m.conversation_id
    WHERE m.sender_id != ? AND m.is_read = 0
    AND (c.user1_id = ? OR c.user2_id = ?)
");
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$count = (int) $stmt->get_result()->fetch_row()[0];

echo json_encode(['count' => $count]);
?>
