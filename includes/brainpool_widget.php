<?php
/**
 * Brain Pool Floating Widget
 * Include this at the bottom of any page (before </body>):
 *   <?php include '../includes/brainpool_widget.php'; ?>
 */

// Ensure session is available (safe to call multiple times)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;

// ── DATA ──
$bp_skills = [];
$bp_categories = [];
$bp_leaderboard = [];
$bp_total_skills = 0;
$bp_total_helpers = 0;
$bp_total_categories = 0;
$my_credits = ['credits_balance' => 0, 'total_earned' => 0, 'total_spent' => 0];

if ($user_id > 0) {
    $db_path = __DIR__ . '/../profile/db.php';
    if (file_exists($db_path)) {
        require_once $db_path;
        if (isset($pdo)) {
            $stmt = $pdo->prepare("SELECT * FROM skill_credits WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $cr = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($cr) {
                $my_credits = $cr;
            } else {
                $pdo->prepare("INSERT IGNORE INTO skill_credits (user_id, credits_balance) VALUES (?, 5)")
                    ->execute([$user_id]);
                $my_credits = ['credits_balance' => 5, 'total_earned' => 0, 'total_spent' => 0];
            }

            $stmt = $pdo->query("SELECT s.*, u.prenom, u.nom, u.avatar, u.filliere FROM skills s JOIN user u ON u.id_user = s.user_id WHERE s.user_id != $user_id ORDER BY s.level DESC, s.created_at DESC");
            $bp_skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $bp_categories = array_unique(array_column($bp_skills, 'category'));

            $stmt = $pdo->query("SELECT sc.*, u.prenom, u.nom, u.avatar FROM skill_credits sc JOIN user u ON u.id_user = sc.user_id ORDER BY sc.total_earned DESC LIMIT 5");
            $bp_leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $bp_total_skills = (int)$pdo->query("SELECT COUNT(*) FROM skills")->fetchColumn();
            $bp_total_helpers = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM skills")->fetchColumn();
            $bp_total_categories = (int)$pdo->query("SELECT COUNT(DISTINCT category) FROM skills")->fetchColumn();
        }
    }
}

$bp_cat_icons = [
    'Informatique' => "\xF0\x9F\x92\xBB",
    'Math\xC3\xA9matiques' => "\xF0\x9F\x93\x90",
    'Physique' => "\xE2\x9A\xA1",
    'Chimie' => "\xF0\x9F\xA7\xAA",
    'Langues' => "\xF0\x9F\x8C\x8D",
    'Droit' => "\xE2\x9A\x96\xEF\xB8\x8F",
    '\xC3\x89conomie' => "\xF0\x9F\x93\x88",
    'M\xC3\xA9decine' => "\xF0\x9F\xA9\xBA",
    'Design' => "\xF0\x9F\x8E\xA8",
    'Marketing' => "\xF0\x9F\x93\xA2",
    'Musique' => "\xF0\x9F\x8E\xB5",
    'Cuisine' => "\xF0\x9F\x8D\xB3",
    'Sport' => "\xE2\x9A\xBD",
    'Autre' => "\xE2\xAD\x90"
];

// Base URL detection for links
$script_dir = str_replace('\\', '/', __DIR__);
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
$rel = ltrim(str_replace($doc_root, '', $script_dir), '/');
$segments = explode('/', $rel);
// __DIR__ is /.../includes => go up one for pages, then into profile
$bp_base = '../profile/';
$sos_base = '../profile/';
?>

<!-- ===== BRAIN POOL WIDGET ===== -->
<style>
/* ── Floating Button ── */
.bpw-fab {
  position: fixed !important;
  bottom: 24px !important;
  left: 24px !important;
  z-index: 99999 !important;
  width: 64px; height: 64px;
  border-radius: 50%;
  border: none;
  background: linear-gradient(135deg, #166534, #15803D);
  color: #fff;
  font-size: 1.6rem;
  cursor: pointer;
  box-shadow: 0 8px 24px rgba(22,101,52,0.35);
  display: flex; align-items: center; justify-content: center;
  transition: transform .25s cubic-bezier(.34,1.56,.64,1), box-shadow .2s ease;
}
.bpw-fab:hover {
  transform: scale(1.12) translateY(-2px);
  box-shadow: 0 12px 32px rgba(22,101,52,0.45);
}
.bpw-fab-badge {
  position: absolute;
  top: -4px; right: -4px;
  background: #DC2626; color: #fff;
  font-size: .65rem; font-weight: 800;
  padding: 2px 7px; border-radius: 999px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
  display: none;
}
.bpw-fab.has-badge .bpw-fab-badge { display: block; }

/* ── Modal Overlay ── */
.bpw-overlay {
  position: fixed; inset: 0; z-index: 99998;
  background: rgba(0,0,0,0.45);
  opacity: 0; pointer-events: none;
  transition: opacity .25s ease;
}
.bpw-overlay.active { opacity: 1; pointer-events: all; }

/* ── Modal Card ── */
.bpw-modal {
  position: fixed;
  top: 50%; left: 50%;
  transform: translate(-50%, -48%) scale(0.96);
  z-index: 99999;
  width: 100%; max-width: 980px; max-height: 92vh;
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 24px 60px rgba(0,0,0,0.25);
  display: flex; flex-direction: column;
  opacity: 0; pointer-events: none;
  transition: opacity .25s ease, transform .3s cubic-bezier(.34,1.56,.64,1);
}
.bpw-modal.active {
  opacity: 1; pointer-events: all;
  transform: translate(-50%, -50%) scale(1);
}

/* ── Modal Header ── */
.bpw-header {
  padding: 1.2rem 1.6rem;
  border-bottom: 1px solid #f3f4f6;
  display: flex; align-items: center; justify-content: space-between;
  flex-shrink: 0;
}
.bpw-header-left { display:flex; align-items:center; gap:.9rem; }
.bpw-header-title { margin:0; font-size:1.35rem; color:#1a2332; font-weight:800; }
.bpw-header-sub { margin:.15rem 0 0; font-size:.82rem; color:#666; }
.bpw-credits-pill {
  background: linear-gradient(135deg,#166534,#15803d); color:#fff;
  padding:.45rem 1rem; border-radius:12px;
  font-weight:700; font-size:.85rem; display:flex; align-items:center; gap:.4rem;
}
.bpw-close {
  width: 40px; height: 40px; border-radius: 50%;
  border: none; background: #f3f4f6; color: #374151;
  font-size: 1.4rem; cursor: pointer; display:flex; align-items:center; justify-content:center;
  transition: background .15s;
}
.bpw-close:hover { background: #e5e7eb; }

/* ── Modal Body (scrollable) ── */
.bpw-body {
  flex: 1; overflow-y: auto; padding: 1.2rem 1.6rem 1.6rem;
}

/* ── SOS Banner ── */
.bpw-sos {
  background: linear-gradient(135deg,#DC2626,#B91C1C); color:#fff;
  border-radius: 14px; padding: 1.2rem 1.4rem;
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 1rem; margin-bottom: 1.4rem;
}
.bpw-sos h3 { margin: 0; font-size: 1.15rem; }
.bpw-sos p { margin: .25rem 0 0; opacity: .92; font-size: .85rem; }
.bpw-sos-btn {
  background: #fff; color: #B91C1C; font-weight: 700;
  padding: .55rem 1.1rem; border-radius: 10px; text-decoration: none; font-size: .85rem;
}

/* ── Layout ── */
.bpw-layout {
  display: grid; grid-template-columns: 1fr 300px; gap: 1.6rem;
}
@media (max-width: 840px) {
  .bpw-layout { grid-template-columns: 1fr; }
  .bpw-modal { max-width: 96vw; border-radius: 16px; }
}

/* ── Category filter ── */
.bpw-cat-filter {
  display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1.2rem;
}
.bpw-cat-chip {
  padding: .35rem .75rem; border-radius: 999px;
  border: 1px solid #e5e7eb; background: #fff; color: #374151;
  font-size: .78rem; font-weight: 500; cursor: pointer;
  transition: all .15s ease;
}
.bpw-cat-chip.active { background: #166534; color: #fff; border-color: #166534; }
.bpw-cat-chip:hover:not(.active) { background: #f9fafb; }

/* ── Skills grid ── */
.bpw-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem;
}
.bpw-card {
  background: #fff; border: 1px solid #f3f4f6; border-radius: 14px;
  padding: 1rem; box-shadow: 0 2px 10px rgba(0,0,0,0.04);
  transition: transform .15s, box-shadow .2s; cursor: pointer;
}
.bpw-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.08);
}
.bpw-card-top { display:flex; align-items:center; gap:.7rem; margin-bottom:.7rem; }
.bpw-avatar { width:40px; height:40px; border-radius:50%; object-fit:cover; }
.bpw-avatar-init {
  width:40px; height:40px; border-radius:50%; background:#166534; color:#fff;
  display:flex; align-items:center; justify-content:center;
  font-weight:700; font-size:.9rem;
}
.bpw-card-name { font-size:.92rem; font-weight:700; color:#1a2332; margin:0; }
.bpw-card-meta { font-size:.75rem; color:#888; margin-top:2px; }
.bpw-skill-title { font-weight:700; color:#1a2332; font-size:1rem; margin:.3rem 0 .2rem; }
.bpw-skill-desc { font-size:.82rem; color:#555; margin:.25rem 0; line-height:1.45; }
.bpw-tag {
  display:inline-block; background:#f0fdf4; color:#166534;
  font-size:.72rem; font-weight:600; padding:.2rem .55rem; border-radius:8px;
}
.bpw-level { display:flex; align-items:center; gap:.25rem; margin-top:.4rem; font-size:.78rem; color:#555; }
.bpw-actions { display:flex; gap:.4rem; margin-top:.8rem; }
.bpw-btn {
  flex:1; padding:.45rem .6rem; border:none; border-radius:10px;
  font-weight:600; font-size:.78rem; cursor:pointer;
  text-align:center; text-decoration:none; display:inline-block;
}
.bpw-btn.primary { background:#166534; color:#fff; }
.bpw-btn.secondary { background:#f3f4f6; color:#374151; }
.bpw-empty { text-align:center; color:#888; padding:1.5rem; font-size:.9rem; }

/* ── Sidebar / Leaderboard ── */
.bpw-sidebar { background:#fff; border:1px solid #f3f4f6; border-radius:14px; padding:1rem; }
.bpw-sidebar h3 { font-size:1rem; color:#1a2332; margin:0 0 .8rem; }
.bpw-rank { display:flex; align-items:center; gap:.6rem; padding:.5rem 0; border-bottom:1px solid #f3f4f6; }
.bpw-rank:last-child { border-bottom:none; }
.bpw-rank-num {
  width:26px; height:26px; border-radius:50%; background:#f0fdf4; color:#166534;
  display:flex; align-items:center; justify-content:center;
  font-weight:800; font-size:.75rem;
}
.bpw-rank-num.gold { background:#fef3c7; color:#b45309; }
.bpw-rank-num.silver { background:#f3f4f6; color:#4b5563; }
.bpw-rank-num.bronze { background:#fff7ed; color:#c2410c; }
.bpw-rank-avatar { width:30px; height:30px; border-radius:50%; object-fit:cover; }
.bpw-rank-name { font-weight:600; font-size:.82rem; color:#1a2332; flex:1; }
.bpw-rank-pts { font-weight:700; font-size:.75rem; color:#166534; }
.bpw-sidebar-actions { margin-top:1rem; display:flex; flex-direction:column; gap:.55rem; }

/* ── Scrollbar polish ── */
.bpw-body::-webkit-scrollbar { width: 8px; }
.bpw-body::-webkit-scrollbar-track { background: transparent; }
.bpw-body::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 999px; }
.bpw-body::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

/* ── Animation pulse on FAB when unseen ── */
@keyframes bpw-pulse {
  0% { box-shadow: 0 8px 24px rgba(22,101,52,0.35); }
  50% { box-shadow: 0 8px 28px rgba(22,101,52,0.55); }
  100% { box-shadow: 0 8px 24px rgba(22,101,52,0.35); }
}
.bpw-fab.pulse { animation: bpw-pulse 2s infinite; }

/* ── Request mini-modal ── */
.bpw-req-overlay {
  position:fixed; inset:0; z-index:100000; background:rgba(0,0,0,0.35);
  opacity:0; pointer-events:none; transition:opacity .2s ease;
}
.bpw-req-overlay.active { opacity:1; pointer-events:all; }
.bpw-req-modal {
  position:fixed; top:50%; left:50%; transform:translate(-50%,-48%) scale(0.96);
  z-index:100001; width:100%; max-width:460px; background:#fff;
  border-radius:18px; box-shadow:0 20px 50px rgba(0,0,0,0.22);
  padding:1.6rem; opacity:0; pointer-events:none;
  transition:opacity .2s ease, transform .25s cubic-bezier(.34,1.56,.64,1);
}
.bpw-req-modal.active { opacity:1; pointer-events:all; transform:translate(-50%,-50%) scale(1); }
.bpw-req-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; }
.bpw-req-head h3 { margin:0; font-size:1.15rem; color:#1a2332; }
.bpw-req-close { width:34px; height:34px; border-radius:50%; border:none; background:#f3f4f6; color:#374151; font-size:1.3rem; cursor:pointer; }
.bpw-req-close:hover { background:#e5e7eb; }
.bpw-field { margin-bottom:.9rem; }
.bpw-field label { display:block; font-size:.78rem; font-weight:700; color:#374151; margin-bottom:.35rem; text-transform:uppercase; letter-spacing:.04em; }
.bpw-field input, .bpw-field select, .bpw-field textarea {
  width:100%; padding:.6rem .75rem; border:1px solid #e5e7eb; border-radius:10px; font-size:.88rem; color:#1a2332; background:#fff; font-family:inherit; outline:none; transition:border-color .15s;
}
.bpw-field input:focus, .bpw-field select:focus, .bpw-field textarea:focus { border-color:#166534; }
.bpw-field textarea { resize:vertical; min-height:72px; }
.bpw-urgency { display:flex; gap:.4rem; }
.bpw-urg-opt { flex:1; padding:.5rem; border:2px solid #e5e7eb; border-radius:10px; text-align:center; cursor:pointer; font-size:.82rem; font-weight:600; color:#374151; background:#fff; transition:all .15s; }
.bpw-urg-opt:hover { border-color:#9ca3af; }
.bpw-urg-opt.active { border-color:#166534; background:#f0fdf4; color:#166534; }
.bpw-req-submit { width:100%; padding:.7rem; border:none; border-radius:12px; background:#166534; color:#fff; font-weight:700; font-size:.92rem; cursor:pointer; }
.bpw-req-submit:hover { background:#14532d; }
.bpw-req-done { text-align:center; padding:2rem 1rem; }
.bpw-req-done h4 { margin:0 0 .5rem; font-size:1.1rem; color:#166534; }
.bpw-req-done p { margin:0; font-size:.88rem; color:#555; }

/* ── Level picker stars ── */
.bpw-level-picker { display:flex; gap:.4rem; font-size:1.6rem; cursor:pointer; user-select:none; }
.bpw-level-star { color:#E5E7EB; transition:color .15s; }
.bpw-level-star.active { color:#F59E0B; }
.bpw-level-star:hover { color:#FCD34D; }

/* ── Footer CTA ── */
.bpw-footer-cta {
  margin-top:1.6rem;
  background: linear-gradient(135deg,#1a2332,#2d3748);
  border-radius:16px;
  padding:1.2rem 1.6rem;
  display:flex;
  align-items:center;
  justify-content:space-between;
  flex-wrap:wrap;
  gap:1rem;
  color:#fff;
}
.bpw-footer-stats {
  display:flex;
  gap:1.5rem;
}
.bpw-fstat {
  display:flex;
  flex-direction:column;
  align-items:center;
}
.bpw-fstat-num {
  font-size:1.3rem;
  font-weight:800;
  color:#4ade80;
}
.bpw-fstat-label {
  font-size:.7rem;
  text-transform:uppercase;
  letter-spacing:.05em;
  opacity:.8;
  margin-top:2px;
}
.bpw-footer-btn {
  background:#fff;
  color:#1a2332;
  padding:.6rem 1.2rem;
  border-radius:12px;
  text-decoration:none;
  font-weight:700;
  font-size:.9rem;
  transition:transform .15s, box-shadow .2s;
  box-shadow:0 4px 12px rgba(0,0,0,0.15);
}
.bpw-footer-btn:hover {
  transform:translateY(-2px);
  box-shadow:0 8px 20px rgba(0,0,0,0.25);
}
</style>

<!-- Floating Button -->
<button class="bpw-fab pulse" id="bpwFab" aria-label="Brain Pool">
  🧠
  <?php if ($my_credits['credits_balance'] > 0): ?>
    <span class="bpw-fab-badge">Cr</span>
  <?php endif; ?>
</button>

<!-- Modal Overlay -->
<div class="bpw-overlay" id="bpwOverlay"></div>

<!-- Modal Card -->
<div class="bpw-modal" id="bpwModal" role="dialog" aria-modal="true" aria-labelledby="bpwTitle">
  <div class="bpw-header">
    <div class="bpw-header-left">
      <div>
        <h2 class="bpw-header-title" id="bpwTitle">🧠 Brain Pool</h2>
        <p class="bpw-header-sub">Échange de compétences — 1 Crédit H = 1 heure d'aide</p>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:.8rem;">
      <?php if ($user_id > 0): ?>
        <div class="bpw-credits-pill">💎 <?= (int)$my_credits['credits_balance'] ?> Crédits H</div>
      <?php endif; ?>
      <button class="bpw-close" id="bpwClose" aria-label="Fermer">×</button>
    </div>
  </div>

  <div class="bpw-body">
    <?php if ($user_id > 0): ?>
      <div class="bpw-sos">
        <div>
          <h3>🆘 SOS Examen</h3>
          <p>Besoin d'aide urgente avant un examen ? Poste ta demande et un helper te répondra rapidement.</p>
        </div>
        <a href="<?php echo $sos_base; ?>sos_examen.php" class="bpw-sos-btn">Demander de l'aide</a>
      </div>
    <?php endif; ?>

    <div class="bpw-layout">
      <div>
        <?php if (!empty($bp_categories)): ?>
          <div class="bpw-cat-filter">
            <button class="bpw-cat-chip active" onclick="bpwFilter('all')">Tout</button>
            <?php foreach ($bp_categories as $cat): ?>
              <button class="bpw-cat-chip" onclick="bpwFilter('<?php echo htmlspecialchars($cat); ?>')">
                <?php echo ($bp_cat_icons[$cat] ?? '⭐') . ' ' . htmlspecialchars($cat); ?>
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="bpw-grid" id="bpwGrid">
          <?php if (empty($bp_skills)): ?>
            <p class="bpw-empty">Aucune compétence proposée pour le moment. Sois le premier !</p>
          <?php else: ?>
            <?php foreach ($bp_skills as $skill): ?>
              <article class="bpw-card" data-category="<?php echo htmlspecialchars($skill['category']); ?>">
                <div class="bpw-card-top">
                  <?php if (!empty($skill['avatar'])): ?>
                    <img src="<?php echo $bp_base . htmlspecialchars($skill['avatar']); ?>" class="bpw-avatar" alt="" />
                  <?php else: ?>
                    <div class="bpw-avatar-init"><?php echo strtoupper(substr($skill['prenom'],0,1).substr($skill['nom'],0,1)); ?></div>
                  <?php endif; ?>
                  <div>
                    <p class="bpw-card-name"><?php echo htmlspecialchars($skill['prenom'].' '.$skill['nom']); ?></p>
                    <p class="bpw-card-meta"><?php echo htmlspecialchars($skill['filliere'] ?: 'Étudiant'); ?></p>
                  </div>
                </div>
                <div class="bpw-skill-title">
                  <?php echo ($bp_cat_icons[$skill['category']] ?? '⭐') . ' ' . htmlspecialchars($skill['skill_name']); ?>
                </div>
                <p class="bpw-skill-desc"><?php echo htmlspecialchars(substr($skill['description'],0,95)); ?>...</p>
                <span class="bpw-tag"><?php echo htmlspecialchars($skill['category']); ?></span>
                <div class="bpw-level">
                  <?php for ($i=0; $i<5; $i++): ?>
                    <span style="color:<?php echo $i < $skill['level'] ? '#F59E0B' : '#E5E7EB'; ?>">★</span>
                  <?php endfor; ?>
                  <span>Niveau <?php echo (int)$skill['level']; ?>/5</span>
                </div>
                <div class="bpw-actions">
                  <a href="<?php echo $bp_base; ?>brainpool.php?action=request&amp;skill_id=<?php echo (int)$skill['id']; ?>" class="bpw-btn primary">Demander</a>
                  <a href="<?php echo $bp_base; ?>brainpool.php" class="bpw-btn secondary">Voir</a>
                </div>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <aside>
        <div class="bpw-sidebar">
          <h3>🏆 Top Helpers</h3>
          <?php if (empty($bp_leaderboard)): ?>
            <p style="font-size:.82rem;color:#888;">Aucun helper pour le moment.</p>
          <?php else: ?>
            <?php foreach ($bp_leaderboard as $idx => $helper): ?>
              <div class="bpw-rank">
                <div class="bpw-rank-num <?php echo $idx===0?'gold':($idx===1?'silver':($idx===2?'bronze':'')); ?>"><?php echo $idx+1; ?></div>
                <?php if (!empty($helper['avatar'])): ?>
                  <img src="<?php echo $bp_base . htmlspecialchars($helper['avatar']); ?>" class="bpw-rank-avatar" alt="" />
                <?php else: ?>
                  <div class="bpw-avatar-init" style="width:30px;height:30px;font-size:.7rem;"><?php echo strtoupper(substr($helper['prenom'],0,1)); ?></div>
                <?php endif; ?>
                <span class="bpw-rank-name"><?php echo htmlspecialchars($helper['prenom'].' '.$helper['nom']); ?></span>
                <span class="bpw-rank-pts"><?php echo (int)$helper['total_earned']; ?> pts</span>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <?php if ($user_id > 0): ?>
          <div class="bpw-sidebar-actions">
            <button class="bpw-btn primary" onclick="bpwOpenOffer()" style="text-align:center;width:100%;border:none;">+ Proposer une compétence</button>
            <button class="bpw-btn secondary" onclick="bpwOpenRequest()" style="text-align:center;width:100%;border:none;">Demander de l'aide</button>
          </div>
        <?php endif; ?>
      </aside>
    </div>

    <!-- Footer CTA -->
    <div class="bpw-footer-cta">
      <div class="bpw-footer-stats">
        <div class="bpw-fstat">
          <span class="bpw-fstat-num"><?php echo $bp_total_skills; ?></span>
          <span class="bpw-fstat-label">compétences</span>
        </div>
        <div class="bpw-fstat">
          <span class="bpw-fstat-num"><?php echo $bp_total_helpers; ?></span>
          <span class="bpw-fstat-label">helpers</span>
        </div>
        <div class="bpw-fstat">
          <span class="bpw-fstat-num"><?php echo $bp_total_categories; ?>+</span>
          <span class="bpw-fstat-label">matieres</span>
        </div>
      </div>
      <a href="<?php echo $bp_base; ?>brainpool.php" class="bpw-footer-btn">
        🚀 Explorer Brain Pool <span style="margin-left:6px;">→</span>
      </a>
    </div>
  </div>
</div>

<!-- Request mini-modal -->
<div class="bpw-req-overlay" id="bpwReqOverlay"></div>
<div class="bpw-req-modal" id="bpwReqModal" role="dialog" aria-modal="true">
  <div class="bpw-req-head">
    <h3>🙋 Demander de l'aide</h3>
    <button class="bpw-req-close" id="bpwReqClose" aria-label="Fermer">×</button>
  </div>

  <div id="bpwReqFormWrap">
    <form method="POST" action="<?php echo $bp_base; ?>request_help.php" id="bpwReqForm">
      <div class="bpw-field">
        <label>Compétence recherchée</label>
        <input type="text" name="skill_name" placeholder="Ex: Algèbre linéaire, Python, Droit des contrats..." required maxlength="80" />
      </div>

      <div class="bpw-field">
        <label>Matière / Domaine</label>
        <input type="text" name="custom_category" placeholder="Ex: Mathématiques, Informatique, Droit, Cuisine..." maxlength="40" />
        <p style="font-size:.72rem;color:#888;margin:.25rem 0 0;">Laisse vide si tu ne sais pas — on te mettra en contact quand même.</p>
      </div>

      <input type="hidden" name="category" value="Autre" />

      <div class="bpw-field">
        <label>Urgence</label>
        <div class="bpw-urgency" id="bpwUrgency">
          <div class="bpw-urg-opt active" data-val="Normal">🟢 Normal</div>
          <div class="bpw-urg-opt" data-val="Urgent">🟠 Urgent</div>
          <div class="bpw-urg-opt" data-val="SOS">🔥 SOS Examen</div>
        </div>
        <input type="hidden" name="urgency" id="bpwUrgencyInput" value="Normal" />
      </div>

      <div class="bpw-field">
        <label>Message (optionnel)</label>
        <textarea name="message" placeholder="Décris brièvement ton besoin, ton niveau, et quand tu es disponible..." maxlength="250"></textarea>
      </div>

      <div class="bpw-field">
        <label>Crédits offerts</label>
        <select name="credits_offered">
          <?php for ($i=1; $i<=5; $i++): ?>
            <option value="<?php echo $i; ?>" <?php echo $i===1 ? 'selected' : ''; ?>><?php echo $i; ?> Crédit<?php echo $i>1?'s':''; ?> H</option>
          <?php endfor; ?>
        </select>
        <p style="font-size:.75rem;color:#666;margin:.3rem 0 0;">💡 <strong>1 Crédit H = 1 heure d'aide</strong> — choisis selon la complexité de ta demande.</p>
      </div>

      <button type="submit" class="bpw-req-submit">Envoyer ma demande →</button>
    </form>
  </div>

  <div class="bpw-req-done" id="bpwReqDone" style="display:none;">
    <h4>✅ Demande envoyée !</h4>
    <p>Un helper va te contacter sous peu.<br/>Tu peux suivre ta demande dans <a href="<?php echo $bp_base; ?>brainpool.php">Brain Pool</a>.</p>
    <button class="bpw-btn secondary" onclick="bpwCloseRequest()" style="margin-top:1rem;">Fermer</button>
  </div>
</div>

<!-- Offer mini-modal -->
<div class="bpw-req-overlay" id="bpwOfferOverlay"></div>
<div class="bpw-req-modal" id="bpwOfferModal" role="dialog" aria-modal="true">
  <div class="bpw-req-head">
    <h3>➕ Proposer une compétence</h3>
    <button class="bpw-req-close" id="bpwOfferClose" aria-label="Fermer">×</button>
  </div>

  <div id="bpwOfferFormWrap">
    <form method="POST" action="<?php echo $bp_base; ?>offer_skill.php" id="bpwOfferForm">
      <div class="bpw-field">
        <label>Compétence proposée</label>
        <input type="text" name="skill_name" placeholder="Ex: Python avancé, Droit des contrats, Piano jazz..." required maxlength="80" />
      </div>

      <div class="bpw-field">
        <label>Matière / Domaine</label>
        <input type="text" name="custom_category" placeholder="Ex: Mathématiques, Informatique, Droit, Cuisine..." maxlength="40" />
        <p style="font-size:.72rem;color:#888;margin:.25rem 0 0;">Précise ta matière pour que les bons étudiants te trouvent.</p>
      </div>

      <input type="hidden" name="category" value="Autre" />

      <div class="bpw-field">
        <label>Niveau de maîtrise</label>
        <div class="bpw-level-picker" id="bpwLevelPicker">
          <?php for ($i=1; $i<=5; $i++): ?>
            <span class="bpw-level-star" data-val="<?php echo $i; ?>">★</span>
          <?php endfor; ?>
        </div>
        <input type="hidden" name="level" id="bpwLevelInput" value="3" />
        <p style="font-size:.75rem;color:#666;margin:.3rem 0 0;" id="bpwLevelLabel">Niveau 3/5 — Bonne maîtrise</p>
      </div>

      <div class="bpw-field">
        <label>Description (optionnel)</label>
        <textarea name="description" placeholder="Décris ce que tu maîtrises, ton expérience, et comment tu peux aider..." maxlength="250"></textarea>
      </div>

      <button type="submit" class="bpw-req-submit">Publier ma compétence →</button>
    </form>
  </div>

  <div class="bpw-req-done" id="bpwOfferDone" style="display:none;">
    <h4>✅ Compétence publiée !</h4>
    <p>Les étudiants pourront maintenant te demander de l'aide.<br/>Gère tes offres dans <a href="<?php echo $bp_base; ?>brainpool.php">Brain Pool</a>.</p>
    <button class="bpw-btn secondary" onclick="bpwCloseOffer()" style="margin-top:1rem;">Fermer</button>
  </div>
</div>

<script>
(function bpwInit() {
  var fab   = document.getElementById('bpwFab');
  var modal = document.getElementById('bpwModal');
  var over  = document.getElementById('bpwOverlay');
  var close = document.getElementById('bpwClose');
  if (!fab || !modal || !over || !close) return;

  function bpwOpen() {
    modal.classList.add('active');
    over.classList.add('active');
    document.body.style.overflow = 'hidden';
    fab.classList.remove('pulse');
  }
  function bpwShut() {
    modal.classList.remove('active');
    over.classList.remove('active');
    document.body.style.overflow = '';
  }

  fab.addEventListener('click', bpwOpen);
  close.addEventListener('click', bpwShut);
  over.addEventListener('click', bpwShut);
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && modal.classList.contains('active')) bpwShut();
  });

  window.bpwFilter = function(cat) {
    console.log('bpwFilter called with:', cat);
    document.querySelectorAll('.bpw-cat-chip').forEach(function(c) {
      var txt = c.textContent.trim();
      var isActive = (cat === 'all' && txt === 'Tout') || (cat !== 'all' && txt.indexOf(cat) !== -1);
      c.classList.toggle('active', isActive);
    });
    var visible = 0;
    document.querySelectorAll('.bpw-card').forEach(function(card) {
      var show = (cat === 'all' || card.dataset.category === cat);
      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    console.log('Cards visible after filter:', visible);
  };

  /* Request mini-modal */
  var reqModal = document.getElementById('bpwReqModal');
  var reqOver  = document.getElementById('bpwReqOverlay');
  var reqClose = document.getElementById('bpwReqClose');
  if (reqModal && reqOver && reqClose) {
    window.bpwOpenRequest = function() {
      reqModal.classList.add('active');
      reqOver.classList.add('active');
    };
    window.bpwCloseRequest = function() {
      reqModal.classList.remove('active');
      reqOver.classList.remove('active');
      var f = document.getElementById('bpwReqForm');
      if (f) f.reset();
      var w = document.getElementById('bpwReqFormWrap');
      var d = document.getElementById('bpwReqDone');
      if (w) w.style.display = '';
      if (d) d.style.display = 'none';
    };
    reqClose.addEventListener('click', window.bpwCloseRequest);
    reqOver.addEventListener('click', window.bpwCloseRequest);
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && reqModal.classList.contains('active')) window.bpwCloseRequest();
    });

    /* Urgency selector */
    document.querySelectorAll('.bpw-urg-opt').forEach(function(opt) {
      opt.addEventListener('click', function() {
        document.querySelectorAll('.bpw-urg-opt').forEach(function(o) { o.classList.remove('active'); });
        opt.classList.add('active');
        var inp = document.getElementById('bpwUrgencyInput');
        if (inp) inp.value = opt.dataset.val;
      });
    });

    /* Form submit */
    var reqForm = document.getElementById('bpwReqForm');
    if (reqForm) {
      reqForm.addEventListener('submit', function() {
        var w = document.getElementById('bpwReqFormWrap');
        var d = document.getElementById('bpwReqDone');
        if (w) w.style.display = 'none';
        if (d) d.style.display = '';
      });
    }
  }

  /* Offer mini-modal */
  var offerModal = document.getElementById('bpwOfferModal');
  var offerOver  = document.getElementById('bpwOfferOverlay');
  var offerClose = document.getElementById('bpwOfferClose');
  if (offerModal && offerOver && offerClose) {
    window.bpwOpenOffer = function() {
      offerModal.classList.add('active');
      offerOver.classList.add('active');
    };
    window.bpwCloseOffer = function() {
      offerModal.classList.remove('active');
      offerOver.classList.remove('active');
      var f = document.getElementById('bpwOfferForm');
      if (f) f.reset();
      var w = document.getElementById('bpwOfferFormWrap');
      var d = document.getElementById('bpwOfferDone');
      if (w) w.style.display = '';
      if (d) d.style.display = 'none';
      updateLevel(3);
    };
    offerClose.addEventListener('click', window.bpwCloseOffer);
    offerOver.addEventListener('click', window.bpwCloseOffer);
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && offerModal.classList.contains('active')) window.bpwCloseOffer();
    });

    /* Level picker */
    function updateLevel(lvl) {
      document.getElementById('bpwLevelInput').value = lvl;
      document.querySelectorAll('.bpw-level-star').forEach(function(s, i) {
        s.classList.toggle('active', i < lvl);
      });
      var labels = ['Débutant','Intermédiaire','Bonne maîtrise','Très bon','Expert'];
      document.getElementById('bpwLevelLabel').textContent = 'Niveau ' + lvl + '/5 — ' + (labels[lvl-1] || '');
    }
    document.querySelectorAll('.bpw-level-star').forEach(function(s) {
      s.addEventListener('click', function() {
        updateLevel(parseInt(s.dataset.val));
      });
      s.addEventListener('mouseenter', function() {
        var v = parseInt(s.dataset.val);
        document.querySelectorAll('.bpw-level-star').forEach(function(st, i) {
          st.style.color = i < v ? '#FCD34D' : '#E5E7EB';
        });
      });
    });
    document.getElementById('bpwLevelPicker').addEventListener('mouseleave', function() {
      var v = parseInt(document.getElementById('bpwLevelInput').value);
      document.querySelectorAll('.bpw-level-star').forEach(function(st, i) {
        st.style.color = '';
        st.classList.toggle('active', i < v);
      });
    });
    updateLevel(3);

    /* Form submit */
    var offerForm = document.getElementById('bpwOfferForm');
    if (offerForm) {
      offerForm.addEventListener('submit', function() {
        var w = document.getElementById('bpwOfferFormWrap');
        var d = document.getElementById('bpwOfferDone');
        if (w) w.style.display = 'none';
        if (d) d.style.display = '';
      });
    }
  }
})();
</script>
<!-- ===== /BRAIN POOL WIDGET ===== -->
