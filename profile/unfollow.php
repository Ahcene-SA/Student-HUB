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

if ($target_id === 0) {
    header("Location: profile.php");
    exit();
}

// Delete the follow record
$stmt = $pdo->prepare("
    DELETE FROM follows
    WHERE follower_id = ? AND following_id = ?
");
$stmt->execute([$my_id, $target_id]);

// Redirect back to the profile we were viewing
header("Location: profile.php?id=" . $target_id);
exit();
?>
