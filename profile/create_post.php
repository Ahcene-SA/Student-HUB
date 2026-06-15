<?php
session_start();

require 'db.php';

// Auto-create posts table if missing (with new columns)
$pdo->exec("
    CREATE TABLE IF NOT EXISTS posts (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        user_id     INT NOT NULL,
        category    VARCHAR(30) NOT NULL DEFAULT 'general',
        title       VARCHAR(255),
        content     TEXT NOT NULL,
        image       VARCHAR(255),
        price       VARCHAR(50),
        location    VARCHAR(255),
        event_date  VARCHAR(50),
        company     VARCHAR(255),
        chambres    INT,
        meuble      VARCHAR(20),
        type_event  VARCHAR(50),
        tarif       VARCHAR(50),
        is_free     VARCHAR(10),
        domaine     VARCHAR(100),
        duree       VARCHAR(50),
        type_stage  VARCHAR(30),
        niveau_etude VARCHAR(50),
        matiere      VARCHAR(100),
        niveau_mentoring VARCHAR(50),
        langue       VARCHAR(50),
        disponibilite VARCHAR(50),
        is_mentor     VARCHAR(10),
        prix_mentoring VARCHAR(50),
        produit       VARCHAR(255),
        etat          VARCHAR(50),
        bonplan_category VARCHAR(50),
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user     (user_id),
        INDEX idx_category (category),
        INDEX idx_created  (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Must be logged in
if (!isset($_SESSION['id_user'])) {
    header('Location: ../persoinfo/signin.php');
    exit();
}

$user_id  = (int) $_SESSION['id_user'];
$category = isset($_POST['category']) ? trim($_POST['category']) : 'general';
$title    = isset($_POST['title'])    ? trim($_POST['title'])    : '';
$content  = isset($_POST['content'])  ? trim($_POST['content'])  : '';

// Validate category
$allowed = ['general', 'immobilier', 'stage', 'events', 'bonplan', 'mentoring'];
if (!in_array($category, $allowed)) {
    $category = 'general';
}

if (empty($content)) {
    $_SESSION['post_error'] = 'Post content cannot be empty.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../profile/profile.php'));
    exit();
}

// Optional image upload
$image = null;
if (!empty($_FILES['image']['name'])) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($ext, $allowed_ext)) {
        $uploadDir = __DIR__ . '/uploads/posts/';
        if (!is_dir($uploadDir)) {
            $made = @mkdir($uploadDir, 0777, true);
            if (!$made) {
                error_log("create_post.php: FAILED to mkdir {$uploadDir}. uid=" . getmyuid() . " gid=" . getmygid());
            } else {
                @chmod($uploadDir, 0777);
            }
        }
        $filename = $user_id . '_' . time() . '.' . $ext;
        $absPath  = $uploadDir . $filename;
        $relPath  = 'uploads/posts/' . $filename;

        $tmpName = $_FILES['image']['tmp_name'] ?? '';
        if (!is_uploaded_file($tmpName)) {
            error_log("create_post.php: not an uploaded file. tmp_name={$tmpName} error=" . ($_FILES['image']['error'] ?? 'N/A'));
        } elseif (!@move_uploaded_file($tmpName, $absPath)) {
            error_log("create_post.php: move_uploaded_file FAILED. src={$tmpName} dst={$absPath} upload_error=" . $_FILES['image']['error']);
        } else {
            $image = $relPath;
            error_log("create_post.php: upload OK. db_path={$relPath} abs_path={$absPath}");
        }
    } else {
        error_log("create_post.php: disallowed extension: {$ext}");
    }
}

// Category-specific fields
$price      = isset($_POST['price'])      ? trim($_POST['price'])      : null;
$location   = isset($_POST['location'])   ? trim($_POST['location'])   : null;
$event_date = isset($_POST['event_date']) ? trim($_POST['event_date']) : null;
$company    = isset($_POST['company'])    ? trim($_POST['company'])    : null;
$chambres   = isset($_POST['chambres'])  ? (int)$_POST['chambres']   : null;
$meuble     = isset($_POST['meuble'])    ? trim($_POST['meuble'])     : null;
$type_event = isset($_POST['type_event']) ? trim($_POST['type_event']) : null;
$tarif      = isset($_POST['tarif'])     ? trim($_POST['tarif'])      : null;
$is_free    = isset($_POST['is_free'])   ? trim($_POST['is_free'])    : null;
$domaine    = isset($_POST['domaine'])    ? trim($_POST['domaine'])    : null;
$duree      = isset($_POST['duree'])     ? trim($_POST['duree'])      : null;
$type_stage = isset($_POST['type_stage']) ? trim($_POST['type_stage']) : null;
$niveau_etude = isset($_POST['niveau_etude']) ? trim($_POST['niveau_etude']) : null;
$matiere    = isset($_POST['matiere'])   ? trim($_POST['matiere'])    : null;
$niveau_mentoring = isset($_POST['niveau_mentoring']) ? trim($_POST['niveau_mentoring']) : null;
$langue     = isset($_POST['langue'])    ? trim($_POST['langue'])     : null;
$disponibilite = isset($_POST['disponibilite']) ? trim($_POST['disponibilite']) : null;
$is_mentor  = isset($_POST['is_mentor'])  ? trim($_POST['is_mentor'])  : null;
$prix_mentoring = isset($_POST['prix_mentoring']) ? trim($_POST['prix_mentoring']) : null;
$produit    = isset($_POST['produit'])   ? trim($_POST['produit'])    : null;
$etat       = isset($_POST['etat'])      ? trim($_POST['etat'])       : null;
$bonplan_category = isset($_POST['bonplan_category']) ? trim($_POST['bonplan_category']) : null;

// Insert post with all fields
$stmt = $pdo->prepare("
    INSERT INTO posts (user_id, category, title, content, image, price, location, event_date, company, chambres, meuble, type_event, tarif, is_free, domaine, duree, type_stage, niveau_etude, matiere, niveau_mentoring, langue, disponibilite, is_mentor, prix_mentoring, produit, etat, bonplan_category)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([$user_id, $category, $title, $content, $image, $price, $location, $event_date, $company, $chambres, $meuble, $type_event, $tarif, $is_free, $domaine, $duree, $type_stage, $niveau_etude, $matiere, $niveau_mentoring, $langue, $disponibilite, $is_mentor, $prix_mentoring, $produit, $etat, $bonplan_category]);

// Redirect based on category
switch ($category) {
    case 'immobilier':
        header('Location: ../pages/immobilier.php');
        break;
    case 'stage':
        header('Location: ../pages/stage.php');
        break;
    case 'events':
        header('Location: ../pages/events.php');
        break;
    case 'bonplan':
        header('Location: ../pages/bonplan.php');
        break;
    case 'mentoring':
        header('Location: ../pages/mentoring.php');
        break;
    default:
        header('Location: ../pages/home.php');
        break;
}
exit();
?>
