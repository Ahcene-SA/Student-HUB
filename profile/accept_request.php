
<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['id_user']) || !isset($_GET['id'])) {
    header('Location: brainpool.php');
    exit;
}

$my_id = (int)$_SESSION['id_user'];
$request_id = (int)$_GET['id'];

// Vérifier que c'est bien la demande adressée à moi
$stmt = $pdo->prepare("
    SELECT * FROM skill_requests
    WHERE id = ? AND helper_id = ? AND status = 'pending'
");
$stmt->execute([$request_id, $my_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    header('Location: brainpool.php');
    exit;
}

// Accepter la demande
$stmt = $pdo->prepare("UPDATE skill_requests SET status = 'accepted', scheduled_at = NOW() WHERE id = ?");
$stmt->execute([$request_id]);

// Notifier le requester
$notif_msg = 'Ta demande d\'aide en ' . $request['skill_name'] . ' a été acceptée !';
$link = '../profile/brainpool.php';
$stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'brainpool', ?, ?)");
$stmt->execute([$request['requester_id'], $notif_msg, $link]);

header('Location: brainpool.php');
exit;
?>
