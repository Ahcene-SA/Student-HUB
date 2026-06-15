
<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../persoinfo/signin.php');
    exit;
}

$my_id = (int)$_SESSION['id_user'];

// Récupérer la filière de l'utilisateur pour matcher
$stmt = $pdo->prepare("SELECT filliere FROM user WHERE id_user = ?");
$stmt->execute([$my_id]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);
$my_filliere = $me['filliere'] ?? '';

// Crédits H
$stmt = $pdo->prepare("SELECT * FROM skill_credits WHERE user_id = ?");
$stmt->execute([$my_id]);
$credits = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$credits) {
    $pdo->prepare("INSERT INTO skill_credits (user_id, credits_balance) VALUES (?, 5)")->execute([$my_id]);
    $credits = ['credits_balance' => 5, 'total_earned' => 0, 'total_spent' => 0];
}

// Helpers d'urgence : les plus hauts niveaux dans chaque catégorie
$stmt = $pdo->query("
    SELECT s.*, u.prenom, u.nom, u.avatar, u.filliere
    FROM skills s
    JOIN user u ON u.id_user = s.user_id
    WHERE s.level >= 4 AND s.user_id != $my_id
    ORDER BY s.level DESC, s.created_at DESC
");
$urgent_helpers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Catégories prédéfinies pour SOS
$sos_categories = ['Mathématiques', 'Physique', 'Informatique', 'Chimie', 'Langues', 'Droit', 'Économie', 'Médecine'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOS Examen - Student Hub</title>
    <link rel="stylesheet" href="../pages/home.css">
    <link rel="stylesheet" href="profile.css?v=5">
    <style>
        .sos-container { max-width: 900px; margin: 120px auto 40px; padding: 0 20px; text-align: center; }
        .sos-title { font-size: 2.2rem; color: #DC2626; margin-bottom: 8px; }
        .sos-sub { color: #666; font-size: 1.05rem; margin-bottom: 30px; }
        .credit-pill { display: inline-block; background: linear-gradient(135deg, #2E5961, #1a3a40); color: #fff; padding: 10px 24px; border-radius: 30px; font-weight: 700; margin-bottom: 30px; }
        .sos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-top: 30px; }
        .sos-card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); cursor: pointer; transition: all .2s; border: 2px solid transparent; text-align: center; }
        .sos-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,0,0,0.12); border-color: #DC2626; }
        .sos-icon { font-size: 2.5rem; margin-bottom: 12px; }
        .sos-name { font-weight: 700; color: #333; font-size: 1.1rem; }
        .sos-desc { color: #888; font-size: 0.85rem; margin-top: 4px; }
        .sos-helpers { margin-top: 40px; text-align: left; }
        .helper-card { display: flex; gap: 14px; padding: 16px; background: #fff; border-radius: 12px; margin-bottom: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.05); align-items: center; }
        .helper-avatar { width: 48px; height: 48px; border-radius: 50%; background: #2E5961; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem; flex-shrink: 0; overflow: hidden; }
        .helper-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .helper-info { flex: 1; }
        .helper-name { font-weight: 700; color: #333; }
        .helper-meta { font-size: 0.8rem; color: #888; }
        .btn-red { background: #DC2626; color: #fff; border: none; padding: 10px 20px; border-radius: 20px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; font-size: 0.85rem; }
        .btn-red:hover { background: #B91C1C; }
        .back-link { display: inline-block; margin-top: 30px; color: #2E5961; text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }

        /* ── AUTRE card ── */
        .sos-autre { background: linear-gradient(135deg,#fef2f2,#fee2e2); border: 2px dashed #fca5a5; }
        .sos-autre:hover { border-color: #DC2626; background: linear-gradient(135deg,#fee2e2,#fecaca); }

        /* ── SOS custom modal ── */
        .sos-modal-overlay { position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.4); opacity:0; pointer-events:none; transition:opacity .2s; }
        .sos-modal-overlay.active { opacity:1; pointer-events:all; }
        .sos-modal { position:fixed; top:50%; left:50%; transform:translate(-50%,-48%) scale(0.96); z-index:10000; width:100%; max-width:460px; background:#fff; border-radius:18px; box-shadow:0 20px 50px rgba(0,0,0,0.22); padding:1.6rem; opacity:0; pointer-events:none; transition:opacity .2s, transform .25s cubic-bezier(.34,1.56,.64,1); }
        .sos-modal.active { opacity:1; pointer-events:all; transform:translate(-50%,-50%) scale(1); }
        .sos-modal-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; }
        .sos-modal-head h3 { margin:0; font-size:1.15rem; color:#1a2332; }
        .sos-modal-close { width:34px; height:34px; border-radius:50%; border:none; background:#f3f4f6; color:#374151; font-size:1.3rem; cursor:pointer; }
        .sos-modal-close:hover { background:#e5e7eb; }
        .sos-field { margin-bottom:.9rem; }
        .sos-field label { display:block; font-size:.78rem; font-weight:700; color:#374151; margin-bottom:.35rem; text-transform:uppercase; letter-spacing:.04em; }
        .sos-field input, .sos-field textarea, .sos-field select { width:100%; padding:.6rem .75rem; border:1px solid #e5e7eb; border-radius:10px; font-size:.88rem; color:#1a2332; background:#fff; font-family:inherit; outline:none; transition:border-color .15s; }
        .sos-field input:focus, .sos-field textarea:focus, .sos-field select:focus { border-color:#DC2626; }
        .sos-field textarea { resize:vertical; min-height:80px; }
        .sos-submit { width:100%; padding:.7rem; border:none; border-radius:12px; background:#DC2626; color:#fff; font-weight:700; font-size:.92rem; cursor:pointer; }
        .sos-submit:hover { background:#B91C1C; }
        .sos-done { text-align:center; padding:2rem 1rem; }
        .sos-done h4 { margin:0 0 .5rem; font-size:1.1rem; color:#DC2626; }
        .sos-done p { margin:0; font-size:.88rem; color:#555; }
    </style>
</head>
<body>

<header class="home_navbar" style="position:fixed;top:0;left:0;right:0;z-index:1000;">
    <div class="leftnav">
        <div class="nav_left">
            <img src="../logo/Student_HUB_LOGO.png" alt="Student HUB" class="logo" onclick="location.href='../pages/home.php'" style="cursor:pointer;">
        </div>
        <div class="nav_center">
            <input type="text" placeholder="Search..." class="search_input">
        </div>
    </div>
    <nav class="nav_right">
        <a href="../pages/home.php">Home</a>
        <a href="../pages/immobilier.php">Immobilier</a>
        <a href="../pages/stage.php">Stage</a>
        <a href="../pages/events.php">Events</a>
        <a href="../pages/mentoring.php">Mentoring</a>
        <a href="../pages/bonplan.php">Bons plans</a>
        <div class="user-menu">
            <button class="post_btn user-menu-trigger" tabindex="0">Mon compte ▾</button>
            <div class="user-menu-dropdown">
                <a href="profile.php?id=<?php echo $my_id; ?>" class="user-menu-item"><span class="user-menu-icon">👤</span> Profile</a>
                <a href="../pages/messages.php" class="user-menu-item"><span class="user-menu-icon">💬</span> Messages</a>
                <a href="#" class="user-menu-item"><span class="user-menu-icon">⚙️</span> Settings</a>
                <a href="#" class="user-menu-item"><span class="user-menu-icon">❓</span> Help</a>
                <div class="user-menu-divider"></div>
                <a href="../persoinfo/signin.php" class="user-menu-item user-menu-logout"><span class="user-menu-icon">🚪</span> Log out</a>
            </div>
        </div>
    </nav>
</header>

<div class="sos-container">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:10px;">
        <div>
            <div class="sos-title">🚨 SOS Examen</div>
            <div class="sos-sub">Clique sur la matière où tu bloques. On te connecte instantanément avec un expert.</div>
        </div>
        <a href="sos_history.php" style="background:#2E5961;color:#fff;padding:10px 18px;border-radius:12px;text-decoration:none;font-weight:700;font-size:.85rem;">📋 Mon historique</a>
    </div>
    <div class="credit-pill">⚡ <?php echo $credits['credits_balance']; ?> Crédits H disponibles</div>

    <!-- MATIERES -->
    <div class="sos-grid">
        <!-- AUTRE : module libre -->
        <div class="sos-card sos-autre" onclick="sosOpenModal()">
            <div class="sos-icon">✏️</div>
            <div class="sos-name" style="color:#DC2626;">Autre</div>
            <div class="sos-desc">Ta matière n'est pas listée ? Écris-la ici.</div>
        </div>

        <?php foreach ($sos_categories as $cat): ?>
            <div class="sos-card" onclick="location.href='brainpool.php?cat=<?php echo urlencode($cat); ?>'">
                <div class="sos-icon">
                    <?php
                    $icons = [
                        'Mathématiques' => '📐', 'Physique' => '⚡', 'Informatique' => '💻',
                        'Chimie' => '🧪', 'Langues' => '🌍', 'Droit' => '⚖️',
                        'Économie' => '📈', 'Médecine' => '🩺'
                    ];
                    echo $icons[$cat] ?? '📚';
                    ?>
                </div>
                <div class="sos-name"><?php echo htmlspecialchars($cat); ?></div>
                <div class="sos-desc">Trouve un helper maintenant</div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- HELPERS URGENCE -->
    <?php if (!empty($urgent_helpers)): ?>
    <div class="sos-helpers">
        <h3 style="color:#2E5961;margin-bottom:16px;">🌟 Experts disponibles (niveau 4-5)</h3>
        <?php foreach ($urgent_helpers as $h): ?>
        <div class="helper-card">
            <div class="helper-avatar">
                <?php if (!empty($h['avatar'])): ?>
                    <img src="<?php echo htmlspecialchars($h['avatar']); ?>" alt="">
                <?php else: ?>
                    <?php echo strtoupper(mb_substr($h['prenom'], 0, 1)); ?>
                <?php endif; ?>
            </div>
            <div class="helper-info">
                <div class="helper-name"><?php echo htmlspecialchars($h['prenom'] . ' ' . $h['nom']); ?></div>
                <div class="helper-meta">
                    <strong><?php echo htmlspecialchars($h['skill_name']); ?></strong> · ★ <?php echo $h['level']; ?>/5
                    · <?php echo htmlspecialchars($h['filliere'] ?: 'Étudiant'); ?>
                </div>
            </div>
            <form method="POST" action="request_help.php">
                <input type="hidden" name="skill_id" value="<?php echo $h['id']; ?>">
                <input type="hidden" name="helper_id" value="<?php echo $h['user_id']; ?>">
                <input type="hidden" name="skill_name" value="<?php echo htmlspecialchars($h['skill_name']); ?>">
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($h['category']); ?>">
                <input type="hidden" name="message" value="SOS Examen - besoin d'aide urgente !">
                <input type="hidden" name="credits_offered" value="2">
                <button type="submit" class="btn-red">Demander (2H)</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- SOS custom modal -->
<div class="sos-modal-overlay" id="sosModalOverlay"></div>
<div class="sos-modal" id="sosModal" role="dialog" aria-modal="true">
  <div class="sos-modal-head">
    <h3>✏️ Demander de l'aide — Autre matière</h3>
    <button class="sos-modal-close" id="sosModalClose" aria-label="Fermer">×</button>
  </div>
  <div id="sosModalFormWrap">
    <form method="POST" action="request_help.php" id="sosModalForm">
      <div class="sos-field">
        <label>Matière / Module</label>
        <input type="text" name="custom_category" placeholder="Ex: Géologie, Astrophysique, Comptabilité, Solfège..." required maxlength="40" />
      </div>
      <div class="sos-field">
        <label>Compétence recherchée</label>
        <input type="text" name="skill_name" placeholder="Ex: Calcul intégral, Balance comptable, Lecture de partition..." required maxlength="80" />
      </div>
      <div class="sos-field">
        <label>Message (optionnel)</label>
        <textarea name="message" placeholder="Décris ton besoin : chapitre, exercice, projet, examen à préparer..." maxlength="250"></textarea>
      </div>
      <div class="sos-field">
        <label>Crédits offerts</label>
        <select name="credits_offered">
          <?php for ($i=1; $i<=5; $i++): ?>
            <option value="<?php echo $i; ?>" <?php echo $i===2 ? 'selected' : ''; ?>><?php echo $i; ?> Crédit<?php echo $i>1?'s':''; ?> H</option>
          <?php endfor; ?>
        </select>
        <p style="font-size:.75rem;color:#666;margin:.3rem 0 0;">💡 <strong>1 Crédit H = 1 heure d'aide</strong></p>
      </div>
      <input type="hidden" name="category" value="Autre" />
      <button type="submit" class="sos-submit">Envoyer ma demande →</button>
    </form>
  </div>
  <div class="sos-done" id="sosModalDone" style="display:none;">
    <h4>✅ Demande envoyée !</h4>
    <p>Un helper va te contacter sous peu.<br/>Tu peux suivre ta demande dans <a href="sos_history.php" style="color:#DC2626;font-weight:700;">ton historique</a>.</p>
    <button class="btn-red" onclick="sosCloseModal()" style="margin-top:1rem;">Fermer</button>
  </div>
</div>

<div style="margin-top:30px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="javascript:history.length > 1 ? history.back() : location.href='brainpool.php'" class="back-link">← Retour</a>
        <a href="brainpool.php" class="back-link">🧠 Brain Pool →</a>
    </div>
</div>

<script>
/* SOS custom modal */
function sosOpenModal() {
    document.getElementById('sosModal').classList.add('active');
    document.getElementById('sosModalOverlay').classList.add('active');
}
function sosCloseModal() {
    document.getElementById('sosModal').classList.remove('active');
    document.getElementById('sosModalOverlay').classList.remove('active');
    var f = document.getElementById('sosModalForm');
    if (f) f.reset();
    document.getElementById('sosModalFormWrap').style.display = '';
    document.getElementById('sosModalDone').style.display = 'none';
}
document.getElementById('sosModalClose').addEventListener('click', sosCloseModal);
document.getElementById('sosModalOverlay').addEventListener('click', sosCloseModal);
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('sosModal').classList.contains('active')) sosCloseModal();
});
document.getElementById('sosModalForm').addEventListener('submit', function() {
    document.getElementById('sosModalFormWrap').style.display = 'none';
    document.getElementById('sosModalDone').style.display = '';
});

var btns = document.querySelectorAll(".user-menu-trigger");
for (var i = 0; i < btns.length; i++) {
    btns[i].addEventListener("click", function(e) {
        e.stopPropagation();
        var menu = this.closest(".user-menu");
        var dd = menu.querySelector(".user-menu-dropdown");
        if (dd) {
            dd.style.display = (dd.style.display === "block") ? "none" : "block";
            dd.style.opacity = (dd.style.display === "block") ? "1" : "0";
            dd.style.pointerEvents = (dd.style.display === "block") ? "all" : "none";
        }
    });
}
document.addEventListener("click", function(e) {
    var menus = document.querySelectorAll(".user-menu");
    for (var i = 0; i < menus.length; i++) {
        var dd = menus[i].querySelector(".user-menu-dropdown");
        if (dd && !menus[i].contains(e.target)) {
            dd.style.display = "none";
            dd.style.opacity = "0";
            dd.style.pointerEvents = "none";
        }
    }
});
</script>

</body>
</html>
