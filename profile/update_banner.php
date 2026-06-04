<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: ../persoinfo/signin.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_banner'])) {
    require 'db.php';

    /* Fetch current user to keep existing paths if no upload */
    $stmt = $pdo->prepare("SELECT avatar, banner FROM user WHERE id_user = ?");
    $stmt->execute([$_SESSION['id_user']]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    $avatar  = $current['avatar'] ?? null;
    $banner  = $current['banner'] ?? null;

    // NOTE: photo uploads are handled by upload_photo.php.
    // This script only updates text fields.

    $stmt = $pdo->prepare("
        UPDATE user SET
            filliere   = ?,
            universite = ?,
            niveau     = ?,
            instagram  = ?,
            linkedin   = ?,
            facebook   = ?,
            avatar     = ?,
            banner     = ?
        WHERE id_user = ?
    ");
    $stmt->execute([
        $_POST['filliere'],
        $_POST['universite'],
        $_POST['niveau'],
        $_POST['instagram'],
        $_POST['linkedin'],
        $_POST['facebook'],
        $avatar,
        $banner,
        $_SESSION['id_user']
    ]);

    $_SESSION['upload_success'] = 'Informations sauvegardées avec succès !';

    $redirect_id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['id_user'];
    header('Location: profile.php?id=' . $redirect_id);
    exit();
}
?>
