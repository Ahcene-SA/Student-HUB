<?php
session_start();

require 'db.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../persoinfo/signin.php');
    exit();
}

$user_id = (int) $_SESSION['id_user'];
$post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;

if ($post_id <= 0) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'profile.php'));
    exit();
}

// Only allow deleting your own posts
$stmt = $pdo->prepare("SELECT user_id, image FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post || $post['user_id'] != $user_id) {
    // Not your post — redirect back
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'profile.php'));
    exit();
}

// Delete image file if exists
if (!empty($post['image']) && file_exists($post['image'])) {
    unlink($post['image']);
}

// Delete from database
$stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
$stmt->execute([$post_id, $user_id]);

// Redirect back
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'profile.php'));
exit();
?>
