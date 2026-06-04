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
$comment_id = isset($_POST['comment_id']) ? (int) $_POST['comment_id'] : 0;

if ($comment_id <= 0) {
    echo json_encode(['error' => 'Invalid comment_id']);
    exit();
}

// Check if already liked
$stmt = $conn->prepare("SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?");
$stmt->bind_param("ii", $comment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$existing = $result->fetch_assoc();

if ($existing) {
    $stmt = $conn->prepare("DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $comment_id, $user_id);
    $stmt->execute();
    $liked = false;
} else {
    $stmt = $conn->prepare("INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $comment_id, $user_id);
    $stmt->execute();
    $liked = true;
}

$stmt = $conn->prepare("SELECT COUNT(*) FROM comment_likes WHERE comment_id = ?");
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$count = (int) $stmt->get_result()->fetch_row()[0];

echo json_encode(['liked' => $liked, 'count' => $count]);
?>
