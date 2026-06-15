<?php
/**
 * Brainpool Widget — Skill Credits
 * Displays a user's credited skills from the skill_credits table.
 */
if (!isset($widget_base_path)) {
    $widget_base_path = '../';
}

require_once __DIR__ . '/../includes/db_config.php';

$pdo = get_pdo_connection();
$user_id = isset($_SESSION['id_user']) ? (int) $_SESSION['id_user'] : 0;

$stmt = $pdo->prepare(
    "SELECT skill_name, credits, created_at
     FROM skill_credits
     WHERE user_id = ?
     ORDER BY credits DESC"
);
$stmt->execute([$user_id]);
$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- Brainpool Widget -->
<div class="brainpool-widget card">
  <h3>🧠 Brainpool</h3>
  <?php if (empty($skills)): ?>
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
