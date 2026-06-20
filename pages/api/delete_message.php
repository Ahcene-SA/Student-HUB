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
$message_id = isset($_POST['message_id']) ? (int) $_POST['message_id'] : 0;

if ($message_id <= 0) {
    echo json_encode(['error' => 'Invalid message_id']);
    exit();
}

// Verify the message belongs to the logged-in user and update it
$stmt = $conn->prepare("UPDATE messages SET is_deleted = 1 WHERE id = ? AND sender_id = ? AND is_deleted = 0");
$stmt->bind_param("ii", $message_id, $user_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Message not found or not authorized']);
}
?>
