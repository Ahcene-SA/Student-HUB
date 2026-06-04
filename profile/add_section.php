<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: ../persoinfo/signin.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_section'])) {
    require 'db.php';

    $title       = isset($_POST['title'])       ? trim($_POST['title'])       : '';
    $description = isset($_POST['description']) ? substr(trim($_POST['description']), 0, 150) : '';
    $date_value  = isset($_POST['date_value'])  ? trim($_POST['date_value'])  : '';

    $stmt = $pdo->prepare("
        INSERT INTO user_sections (user_id, type, title, description, date_value)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['id_user'],
        $_POST['type'],
        $title,
        $description,
        $date_value
    ]);
    $redirect_id = isset($_POST['profile_id']) ? (int)$_POST['profile_id'] : $_SESSION['id_user'];
    header('Location: profile.php?id=' . $redirect_id);
    exit();
}
?>
