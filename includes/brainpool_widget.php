<?php
/**
 * Brainpool Widget — Skill Credits
 * Safe wrapper: never fatal-errors the whole page.
 */
if (!isset($widget_base_path)) { $widget_base_path = '../'; }

require_once __DIR__ . '/../includes/db_config.php';

$skills = [];
$error_msg = null;

try {
    $pdo = get_pdo_connection();

    // Auto-create table if missing (silently ignore if we lack CREATE privileges)
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS skill_credits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                skill_name VARCHAR(100) NOT NULL,
                credits INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES user(id_user) ON DELETE CASCADE,
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (PDOException $e) {
        // If CREATE fails (e.g. no privileges), keep going and see if SELECT works
    }

    $user_id = isset($_SESSION['id_user']) ? (int) $_SESSION['id_user'] : 0;

    $stmt = $pdo->prepare(
        "SELECT skill_name, credits, created_at
         FROM skill_credits
         WHERE user_id = ?
         ORDER BY credits DESC"
    );
    $stmt->execute([$user_id]);
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_msg = $e->getMessage();
    // Log but don't crash the whole page
    error_log('Brainpool widget error: ' . $error_msg);
}
?>
<!-- Brainpool Widget -->
<div class="brainpool-widget card">
  <h3>🧠 Brainpool</h3>
  <?php if ($error_msg): ?>
    <?php /* Silent fail — uncomment below only while debugging */ ?>
    <!-- <p class="bp-empty">Erreur de chargement.</p> -->
    <?php /* In production, just render nothing so the rest of the page survives */ ?>
  <?php elseif (empty($skills)): ?>
    <p class="bp-empty">Aucune compétence créditée pour le moment.</p>
  <?php else: ?>
    <ul class="bp-list">
      <?php foreach ($skills as $s): ?>
        <li class="bp-item">
          <span class="bp-skill"><?php echo htmlspecialchars($s['skill_name']); ?></span>
          <span class="bp-credits"><?php echo (int) $s['credits']; ?> pts</span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

<style>
  .brainpool-widget { margin-top: 16px; }
  .brainpool-widget h3 {
    margin: 0 0 12px;
    font-size: 1rem;
    font-weight: 700;
    color: #1a2332;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .bp-empty {
    color: #9ca3af;
    font-size: 0.85rem;
    text-align: center;
    padding: 12px 0;
    font-style: italic;
    margin: 0;
  }
  .bp-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .bp-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #F8FAFB;
    border-radius: 10px;
    padding: 8px 12px;
    transition: background 0.15s ease;
  }
  .bp-item:hover { background: #eef2f5; }
  .bp-skill {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1a2332;
  }
  .bp-credits {
    font-size: 0.8rem;
    font-weight: 700;
    color: #2E5961;
    background: rgba(46,89,97,0.10);
    padding: 3px 8px;
    border-radius: 999px;
    flex-shrink: 0;
  }
</style>
