
<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['id_user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: brainpool.php');
    exit;
}

$my_id = (int)$_SESSION['id_user'];
$skill_name = trim($_POST['skill_name'] ?? '');
$category = trim($_POST['custom_category'] ?? '');
if (empty($category)) {
    $category = trim($_POST['category'] ?? '');
}
$description = trim($_POST['description'] ?? '');
$level = (int)($_POST['level'] ?? 3);

if (empty($skill_name) || empty($category)) {
    header('Location: brainpool.php');
    exit;
}

$stmt = $pdo->prepare("INSERT INTO skills (user_id, skill_name, category, description, level) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$my_id, $skill_name, $category, $description, $level]);

header('Location: brainpool.php');
exit;
?>
