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
$post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;

if ($post_id <= 0) {
    echo json_encode(['error' => 'Invalid post_id']);
    exit();
}

// Check if already liked
$stmt = $conn->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$existing = $result->fetch_assoc();

if ($existing) {
    // Unlike
    $stmt = $conn->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $liked = false;
} else {
    // Like
    $stmt = $conn->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $liked = true;
}

// Get new count
$stmt = $conn->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$count = (int) $stmt->get_result()->fetch_row()[0];

echo json_encode(['liked' => $liked, 'count' => $count]);
?>
