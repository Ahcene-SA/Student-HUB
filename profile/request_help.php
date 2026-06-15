
<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['id_user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: brainpool.php');
    exit;
}

$my_id = (int)$_SESSION['id_user'];
$skill_id = isset($_POST['skill_id']) ? (int)$_POST['skill_id'] : null;
$helper_id = isset($_POST['helper_id']) ? (int)$_POST['helper_id'] : null;
$skill_name = trim($_POST['skill_name'] ?? '');
$category = trim($_POST['custom_category'] ?? '');
if (empty($category)) {
    $category = trim($_POST['category'] ?? '');
}
$message = trim($_POST['message'] ?? '');
$credits_offered = (int)($_POST['credits_offered'] ?? 1);
$urgency = trim($_POST['urgency'] ?? 'Normal');

// Prefix urgency level to message
if (!empty($urgency) && $urgency !== 'Normal') {
    $message = '[' . strtoupper($urgency) . '] ' . $message;
}

if (empty($skill_name) || empty($category)) {
    header('Location: brainpool.php');
    exit;
}

// Vérifier que l'utilisateur a assez de crédits
$stmt = $pdo->prepare("SELECT credits_balance FROM skill_credits WHERE user_id = ?");
$stmt->execute([$my_id]);
$credits = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$credits || $credits['credits_balance'] < $credits_offered) {
    header('Location: brainpool.php?error=not_enough_credits');
    exit;
}

// Créer la demande
$stmt = $pdo->prepare("
    INSERT INTO skill_requests (requester_id, helper_id, skill_id, skill_name, category, message, credits_offered)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([$my_id, $helper_id, $skill_id, $skill_name, $category, $message, $credits_offered]);

$request_id = $pdo->lastInsertId();

// Notifier le helper s'il est défini
if ($helper_id) {
    $notif_msg = 'Quelqu\'un demande ton aide en : ' . $skill_name;
    $link = '../profile/brainpool.php';
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'brainpool', ?, ?)");
    $stmt->execute([$helper_id, $notif_msg, $link]);
}

header('Location: brainpool.php');
exit;
?>
