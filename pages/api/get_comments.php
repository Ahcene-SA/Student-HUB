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

$post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
if ($post_id <= 0) {
    echo json_encode(['error' => 'Invalid post_id']);
    exit();
}

$user_id = (int) $_SESSION['id_user'];

// Fetch comments with author info and like counts
$stmt = $conn->prepare("
    SELECT
        c.id,
        c.content,
        c.created_at,
        u.prenom,
        u.nom,
        u.avatar,
        (SELECT COUNT(*) FROM comment_likes WHERE comment_id = c.id) AS like_count,
        EXISTS(SELECT 1 FROM comment_likes WHERE comment_id = c.id AND user_id = ?) AS user_liked
    FROM comments c
    JOIN user u ON u.id_user = c.user_id
    WHERE c.post_id = ?
    ORDER BY c.created_at ASC
");
$stmt->bind_param("ii", $user_id, $post_id);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = [
        'id' => (int)$row['id'],
        'content' => $row['content'],
        'created_at' => $row['created_at'],
        'author' => $row['prenom'] . ' ' . $row['nom'],
        'avatar' => $row['avatar'],
        'like_count' => (int)$row['like_count'],
        'user_liked' => (bool)$row['user_liked']
    ];
}

// Get post like count and user liked status
$stmt = $conn->prepare("
    SELECT
        (SELECT COUNT(*) FROM post_likes WHERE post_id = ?) AS like_count,
        EXISTS(SELECT 1 FROM post_likes WHERE post_id = ? AND user_id = ?) AS user_liked
");
$stmt->bind_param("iii", $post_id, $post_id, $user_id);
$stmt->execute();
$postMeta = $stmt->get_result()->fetch_assoc();

echo json_encode([
    'comments' => $comments,
    'post_like_count' => (int)$postMeta['like_count'],
    'post_user_liked' => (bool)$postMeta['user_liked']
]);
?>
