
<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['id_user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: brainpool.php');
    exit;
}

$my_id = (int)$_SESSION['id_user'];
$request_id = (int)$_POST['request_id'];

// Vérifier la demande
$stmt = $pdo->prepare("
    SELECT * FROM skill_requests
    WHERE id = ? AND helper_id = ? AND status = 'accepted'
");
$stmt->execute([$request_id, $my_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    header('Location: brainpool.php');
    exit;
}

// Marquer comme terminée
$stmt = $pdo->prepare("UPDATE skill_requests SET status = 'completed', completed_at = NOW() WHERE id = ?");
$stmt->execute([$request_id]);

// Transférer les crédits : retirer au requester, ajouter au helper
$credits = $request['credits_offered'];
$requester_id = $request['requester_id'];

// Retirer au requester
$stmt = $pdo->prepare("
    UPDATE skill_credits
    SET credits_balance = credits_balance - ?, total_spent = total_spent + ?
    WHERE user_id = ?
");
$stmt->execute([$credits, $credits, $requester_id]);

// Ajouter au helper
$stmt = $pdo->prepare("
    UPDATE skill_credits
    SET credits_balance = credits_balance + ?, total_earned = total_earned + ?
    WHERE user_id = ?
");
$stmt->execute([$credits, $credits, $my_id]);

// Notifier le requester
$notif_msg = 'Ta session d\'aide en ' . $request['skill_name'] . ' est terminée. ' . $credits . ' Crédit(s) H ont été transférés.';
$link = '../profile/brainpool.php';
$stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'brainpool', ?, ?)");
$stmt->execute([$requester_id, $notif_msg, $link]);

header('Location: brainpool.php');
exit;
?>
