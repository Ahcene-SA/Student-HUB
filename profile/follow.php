<?php
session_start();

require 'db.php';

// Must be logged in
if (!isset($_SESSION['id_user'])) {
    header("Location: ../persoinfo/signin.php");
    exit();
}

$my_id = (int) $_SESSION['id_user'];

// Get target user ID from form POST
$target_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;

// Don't allow following yourself
if ($target_id === 0 || $target_id === $my_id) {
    header("Location: profile.php?id=" . $target_id);
    exit();
}

// Check if already following (prevent duplicate)
$stmt = $pdo->prepare("
    SELECT id FROM follows
    WHERE follower_id = ? AND following_id = ?
");
$stmt->execute([$my_id, $target_id]);

if (!$stmt->fetch()) {
    // Insert follow record
    $stmt = $pdo->prepare("
        INSERT INTO follows (follower_id, following_id)
        VALUES (?, ?)
    ");
    $stmt->execute([$my_id, $target_id]);
}

// Redirect back to the profile we were viewing
header("Location: profile.php?id=" . $target_id);
exit();
?>
