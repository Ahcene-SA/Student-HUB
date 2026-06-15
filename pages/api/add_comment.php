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
$post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

if ($post_id <= 0 || empty($content)) {
    echo json_encode(['error' => 'Invalid data']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $post_id, $user_id, $content);
$stmt->execute();
$comment_id = $conn->insert_id;

// Fetch the new comment with author info
$stmt = $conn->prepare("
    SELECT c.id, c.content, c.created_at, u.prenom, u.nom, u.avatar
    FROM comments c
    JOIN user u ON u.id_user = c.user_id
    WHERE c.id = ?
");
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

echo json_encode([
    'success' => true,
    'comment' => [
        'id' => (int)$row['id'],
        'content' => $row['content'],
        'created_at' => $row['created_at'],
        'author' => $row['prenom'] . ' ' . $row['nom'],
        'avatar' => $row['avatar'],
        'like_count' => 0,
        'user_liked' => false
    ]
]);
?>
