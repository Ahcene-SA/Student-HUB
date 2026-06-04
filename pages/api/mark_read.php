<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_user'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$conn = new mysqli("127.0.0.1", "root", "", "devweb", 3306);
if ($conn->connect_error) {
    echo json_encode(['error' => 'DB connection failed']);
    exit();
}

$user_id = (int) $_SESSION['id_user'];
$conversation_id = isset($_POST['conversation_id']) ? (int) $_POST['conversation_id'] : 0;

if ($conversation_id <= 0) {
    echo json_encode(['error' => 'Invalid conversation_id']);
    exit();
}

$stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ? AND is_read = 0");
$stmt->bind_param("ii", $conversation_id, $user_id);
$stmt->execute();

echo json_encode(['success' => true, 'affected' => $stmt->affected_rows]);
?>
