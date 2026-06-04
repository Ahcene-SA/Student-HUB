<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_user'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$conn = new mysqli("127.0.0.1", "root", "", "devweb", 3306);
if ($conn->connect_error) {
    echo json_encode(['error' => 'DB connection failed']);
    exit();
}

$user_id = (int) $_SESSION['id_user'];
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($q) < 2) {
    echo json_encode(['users' => []]);
    exit();
}

$search = '%' . $q . '%';
$stmt = $conn->prepare("
    SELECT id_user, prenom, nom, avatar
    FROM user
    WHERE id_user != ? AND (prenom LIKE ? OR nom LIKE ? OR username LIKE ?)
    LIMIT 10
");
$stmt->bind_param("isss", $user_id, $search, $search, $search);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = [
        'id' => (int)$row['id_user'],
        'name' => $row['prenom'] . ' ' . $row['nom'],
        'avatar' => $row['avatar']
    ];
}

echo json_encode(['users' => $users]);
?>
