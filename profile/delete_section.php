<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: ../persoinfo/signin.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'db.php';
    $stmt = $pdo->prepare("
        DELETE FROM user_sections
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([
        $_POST['id'],
        $_SESSION['id_user']
    ]);
    $redirect_id = isset($_POST['profile_id']) ? (int)$_POST['profile_id'] : $_SESSION['id_user'];
    header('Location: profile.php?id=' . $redirect_id);
    exit();
}
?>
