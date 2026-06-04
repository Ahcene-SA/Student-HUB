<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: ../persoinfo/signin.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_info'])) {
    require 'db.php';
    $stmt = $pdo->prepare("
        UPDATE user SET
            phone      = ?,
            promotion  = ?,
            specialite = ?,
            niveau     = ?
        WHERE id_user = ?
    ");
    $stmt->execute([
        $_POST['phone'],
        $_POST['promotion'],
        $_POST['specialite'],
        $_POST['niveau'],
        $_SESSION['id_user']
    ]);
    $redirect_id = isset($_POST['profile_id']) ? (int)$_POST['profile_id'] : $_SESSION['id_user'];
    header('Location: profile.php?id=' . $redirect_id);
    exit();
}
?>
