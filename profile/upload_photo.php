<?php
session_start();

if (!isset($_SESSION['id_user'])) {
    header('Location: ../persoinfo/signin.php');
    exit();
}

require 'db.php';

$user_id = $_SESSION['id_user'];
$errors  = [];

// Get current photo paths
$stmt = $pdo->prepare("SELECT avatar, banner FROM user WHERE id_user = ?");
$stmt->execute([$user_id]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);
$avatar  = $current['avatar'] ?? null;
$banner  = $current['banner'] ?? null;

// Ensure upload directories exist (absolute paths so they work regardless of PHP working dir)
$avatarDir = __DIR__ . '/uploads/avatars/';
$bannerDir = __DIR__ . '/uploads/banners/';
if (!is_dir($avatarDir)) {
    $ok = @mkdir($avatarDir, 0777, true);
    if ($ok) @chmod($avatarDir, 0777);
}
if (!is_dir($bannerDir)) {
    $ok = @mkdir($bannerDir, 0777, true);
    if ($ok) @chmod($bannerDir, 0777);
}

$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

/* Handle avatar upload */
if (!empty($_FILES['avatar']['name'])) {
    if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Erreur lors de l\'upload de l\'avatar (code ' . $_FILES['avatar']['error'] . ').';
    } else {
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Format d\'avatar non autorisé. Formats acceptés : jpg, jpeg, png, gif, webp.';
        } else {
            // Delete old avatar if exists
            $oldAvatarPath = !empty($avatar) ? (__DIR__ . '/' . $avatar) : null;
            if ($oldAvatarPath && file_exists($oldAvatarPath)) {
                @unlink($oldAvatarPath);
            }
            $filename = $user_id . '_' . time() . '.' . $ext;
            $avatar = 'uploads/avatars/' . $filename;
            if (!@move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarDir . $filename)) {
                $errors[] = 'Impossible d\'enregistrer l\'avatar sur le serveur.';
                $avatar = $current['avatar'] ?? null; // rollback path
            }
        }
    }
}

/* Handle banner upload */
if (!empty($_FILES['banner']['name'])) {
    if ($_FILES['banner']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Erreur lors de l\'upload de la bannière (code ' . $_FILES['banner']['error'] . ').';
    } else {
        $ext = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Format de bannière non autorisé. Formats acceptés : jpg, jpeg, png, gif, webp.';
        } else {
            // Delete old banner if exists
            $oldBannerPath = !empty($banner) ? (__DIR__ . '/' . $banner) : null;
            if ($oldBannerPath && file_exists($oldBannerPath)) {
                @unlink($oldBannerPath);
            }
            $filename = $user_id . '_' . time() . '.' . $ext;
            $banner = 'uploads/banners/' . $filename;
            if (!@move_uploaded_file($_FILES['banner']['tmp_name'], $bannerDir . $filename)) {
                $errors[] = 'Impossible d\'enregistrer la bannière sur le serveur.';
                $banner = $current['banner'] ?? null; // rollback path
            }
        }
    }
}

// Update database
$stmt = $pdo->prepare("UPDATE user SET avatar = ?, banner = ? WHERE id_user = ?");
$stmt->execute([$avatar, $banner, $user_id]);

if (!empty($errors)) {
    $_SESSION['upload_errors'] = $errors;
} else {
    $_SESSION['upload_success'] = 'Photo mise à jour avec succès !';
}

$redirect_id = isset($_GET['id']) ? (int)$_GET['id'] : $user_id;
header('Location: profile.php?id=' . $redirect_id);
exit();
?>
