
<?php
require_once __DIR__ . '/db.php';

// Table des compétences proposées
$pdo->exec("
    CREATE TABLE IF NOT EXISTS skills (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        skill_name VARCHAR(100) NOT NULL,
        category VARCHAR(50) NOT NULL,
        description TEXT,
        level INT DEFAULT 3 COMMENT '1=débutant, 5=expert',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_category (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Table des demandes d'aide
$pdo->exec("
    CREATE TABLE IF NOT EXISTS skill_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        requester_id INT NOT NULL,
        helper_id INT,
        skill_id INT,
        skill_name VARCHAR(100),
        category VARCHAR(50),
        status ENUM('pending','accepted','completed','cancelled') DEFAULT 'pending',
        message TEXT,
        credits_offered INT DEFAULT 1,
        scheduled_at DATETIME,
        completed_at DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_requester (requester_id),
        INDEX idx_helper (helper_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Table des crédits H par utilisateur
$pdo->exec("
    CREATE TABLE IF NOT EXISTS skill_credits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        credits_balance INT DEFAULT 5 COMMENT '5 crédits offerts à l inscription',
        total_earned INT DEFAULT 0,
        total_spent INT DEFAULT 0,
        UNIQUE KEY unique_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Table des avis après session
$pdo->exec("
    CREATE TABLE IF NOT EXISTS skill_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        reviewer_id INT NOT NULL,
        reviewed_id INT NOT NULL,
        rating INT NOT NULL COMMENT '1-5 étoiles',
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_request (request_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Insérer 5 crédits à tous les utilisateurs existants qui n'en ont pas
$stmt = $pdo->query("
    INSERT IGNORE INTO skill_credits (user_id, credits_balance, total_earned, total_spent)
    SELECT id_user, 5, 0, 0 FROM user
");

echo "<!DOCTYPE html><html><head><style>body{font-family:Inter,sans-serif;text-align:center;padding:60px;}h2{color:#2E5961;}a{color:#2E5961;text-decoration:none;font-weight:600;}a:hover{text-decoration:underline;}</style></head><body>";
echo "<h2>🧠 Brain Pool installé !</h2>";
echo "<p>Les tables skills, skill_requests, skill_credits et skill_reviews ont été créées.</p>";
echo "<p>5 Crédits H ont été offerts à tous les utilisateurs.</p>";
echo "<a href='brainpool.php'>→ Découvrir Brain Pool</a>";
echo "</body></html>";
?>
