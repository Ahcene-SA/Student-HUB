
<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../persoinfo/signin.php');
    exit;
}

$my_id = (int)$_SESSION['id_user'];

// Récupérer mes infos
$stmt = $pdo->prepare("SELECT * FROM user WHERE id_user = ?");
$stmt->execute([$my_id]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);

// Crédits H
$stmt = $pdo->prepare("SELECT * FROM skill_credits WHERE user_id = ?");
$stmt->execute([$my_id]);
$credits = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$credits) {
    $pdo->prepare("INSERT INTO skill_credits (user_id, credits_balance) VALUES (?, 5)")->execute([$my_id]);
    $credits = ['credits_balance' => 5, 'total_earned' => 0, 'total_spent' => 0];
}

// Compétences proposées par les autres (sauf moi)
$stmt = $pdo->query("
    SELECT s.*, u.prenom, u.nom, u.avatar
    FROM skills s
    JOIN user u ON u.id_user = s.user_id
    WHERE s.user_id != $my_id
    ORDER BY s.level DESC, s.created_at DESC
");
$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Catégories uniques pour le filtre
$categories = array_unique(array_column($skills, 'category'));

// Mes demandes envoyées
$stmt = $pdo->prepare("
    SELECT sr.*, u.prenom, u.nom, u.avatar as helper_avatar
    FROM skill_requests sr
    LEFT JOIN user u ON u.id_user = sr.helper_id
    WHERE sr.requester_id = ?
    ORDER BY sr.created_at DESC
");
$stmt->execute([$my_id]);
$my_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Demandes reçues à traiter
$stmt = $pdo->prepare("
    SELECT sr.*, u.prenom, u.nom, u.avatar as requester_avatar
    FROM skill_requests sr
    JOIN user u ON u.id_user = sr.requester_id
    WHERE sr.helper_id = ? AND sr.status = 'pending'
    ORDER BY sr.created_at DESC
");
$stmt->execute([$my_id]);
$incoming_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sessions en cours (accepted)
$stmt = $pdo->prepare("
    SELECT sr.*, req.prenom as req_prenom, req.nom as req_nom, hel.prenom as hel_prenom, hel.nom as hel_nom
    FROM skill_requests sr
    JOIN user req ON req.id_user = sr.requester_id
    JOIN user hel ON hel.id_user = sr.helper_id
    WHERE (sr.requester_id = ? OR sr.helper_id = ?) AND sr.status = 'accepted'
    ORDER BY sr.scheduled_at ASC
");
$stmt->execute([$my_id, $my_id]);
$active_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Leaderboard top helpers (ceux qui ont le plus aidé = total_earned)
$stmt = $pdo->query("
    SELECT sc.*, u.prenom, u.nom, u.avatar
    FROM skill_credits sc
    JOIN user u ON u.id_user = sc.user_id
    ORDER BY sc.total_earned DESC
    LIMIT 5
");
$leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mes compétences
$stmt = $pdo->prepare("SELECT * FROM skills WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$my_id]);
$my_skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Catégories prédéfinies
$skill_categories = ['Informatique', 'Mathématiques', 'Physique', 'Langues', 'Design', 'Marketing', 'Droit', 'Médecine', 'Musique', 'Cuisine', 'Sport', 'Autre'];

function timeAgo($date) {
    $t = strtotime($date);
    $d = time() - $t;
    if ($d < 60) return 'à l\'instant';
    if ($d < 3600) return 'il y a ' . round($d/60) . ' min';
    if ($d < 86400) return 'il y a ' . round($d/3600) . ' h';
    if ($d < 604800) return 'il y a ' . round($d/86400) . ' j';
    return date('d/m/Y', $t);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brain Pool - Student Hub</title>
    <link rel="stylesheet" href="../pages/home.css">
    <link rel="stylesheet" href="profile.css?v=5">
    <style>
        .bp-container { max-width: 1100px; margin: 120px auto 40px; padding: 0 20px; }
        .bp-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .bp-header h2 { margin: 0; color: #2E5961; font-size: 1.6rem; }
        .credit-badge { background: linear-gradient(135deg, #2E5961, #1a3a40); color: #fff; padding: 10px 20px; border-radius: 30px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(46,89,97,0.3); }
        .credit-badge small { font-weight: 400; opacity: 0.85; font-size: 0.75rem; }
        .bp-actions { display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap; }
        .bp-btn { padding: 12px 24px; border-radius: 12px; font-weight: 700; cursor: pointer; border: none; font-size: 0.95rem; text-decoration: none; display: inline-block; transition: transform .15s, box-shadow .15s; }
        .bp-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.12); }
        .bp-btn-primary { background: #2E5961; color: #fff; }
        .bp-btn-danger { background: #EF4444; color: #fff; }
        .bp-btn-success { background: #10B981; color: #fff; }
        .bp-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }
        @media (max-width: 768px) { .bp-grid { grid-template-columns: 1fr; } }
        .bp-card { background: #fff; border-radius: 16px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 20px; }
        .bp-card h3 { margin: 0 0 14px; color: #2E5961; font-size: 1.15rem; display: flex; align-items: center; gap: 8px; }
        .skill-tag { display: inline-flex; align-items: center; padding: 6px 12px; background: #eef6f7; color: #2E5961; border-radius: 20px; font-size: 0.82rem; font-weight: 600; margin: 4px 4px 0 0; }
        .skill-tag .level { display: inline-block; margin-left: 6px; font-size: 0.7rem; background: #2E5961; color: #fff; padding: 2px 6px; border-radius: 8px; }
        .skill-card { display: flex; gap: 14px; padding: 14px; border: 1px solid #eef6f7; border-radius: 12px; margin-bottom: 10px; transition: all .15s; align-items: flex-start; }
        .skill-card:hover { border-color: #2E5961; box-shadow: 0 4px 12px rgba(46,89,97,0.08); }
        .skill-avatar { width: 44px; height: 44px; border-radius: 50%; background: #2E5961; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; flex-shrink: 0; overflow: hidden; }
        .skill-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .skill-info { flex: 1; }
        .skill-name { font-weight: 700; color: #333; font-size: 0.95rem; }
        .skill-meta { font-size: 0.8rem; color: #888; margin-top: 2px; }
        .skill-desc { font-size: 0.85rem; color: #666; margin-top: 4px; }
        .request-card { padding: 14px; background: #f8fafb; border-radius: 12px; margin-bottom: 10px; border-left: 4px solid #2E5961; }
        .request-card.pending { border-left-color: #F59E0B; }
        .request-card.accepted { border-left-color: #3B82F6; }
        .request-card.completed { border-left-color: #10B981; }
        .request-actions { display: flex; gap: 8px; margin-top: 10px; }
        .lb-item { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .lb-item:last-child { border-bottom: none; }
        .lb-rank { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; color: #fff; background: #ccc; }
        .lb-rank.gold { background: #FFD700; color: #333; }
        .lb-rank.silver { background: #C0C0C0; color: #333; }
        .lb-rank.bronze { background: #CD7F32; }
        .sos-banner { background: linear-gradient(135deg, #EF4444, #DC2626); color: #fff; padding: 20px; border-radius: 16px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .sos-banner h3 { margin: 0; font-size: 1.2rem; }
        .sos-banner p { margin: 4px 0 0; opacity: 0.9; font-size: 0.9rem; }
        .empty-state { text-align: center; padding: 30px; color: #999; }
        .category-filter { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
        .category-filter a { padding: 6px 14px; border-radius: 20px; background: #f1f5f9; color: #64748b; text-decoration: none; font-size: 0.82rem; font-weight: 600; transition: all .15s; }
        .category-filter a:hover, .category-filter a.active { background: #2E5961; color: #fff; }
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

<div class="bp-container">
    <div class="bp-header">
        <h2>🧠 Brain Pool — L'économie du savoir</h2>
        <div class="credit-badge">
            ⚡ <?php echo $credits['credits_balance']; ?> Crédits H
            <small>(gagnés: <?php echo $credits['total_earned']; ?> / dépensés: <?php echo $credits['total_spent']; ?>)</small>
        </div>
    </div>

    <!-- SOS EXAMEN -->
    <div class="sos-banner">
        <div>
            <h3>🚨 SOS Examen</h3>
            <p>Urgence avant un examen ? Trouve immédiatement un helper dans ta matière.</p>
        </div>
        <a href="sos_examen.php" class="bp-btn" style="background:#fff;color:#DC2626;">J'ai besoin d'aide →</a>
    </div>

    <div class="bp-actions">
        <button class="bp-btn bp-btn-primary" onclick="bpwOpenOffer()">➕ Proposer une compétence</button>
        <button class="bp-btn bp-btn-success" onclick="bpwOpenRequest()">🙋 Demander de l'aide</button>
    </div>

    <div class="bp-grid">
        <div class="bp-main">
            <!-- FILTRE CATEGORIES -->
            <div class="category-filter">
                <a href="brainpool.php" class="<?php echo empty($_GET['cat']) ? 'active' : ''; ?>">Tout</a>
                <?php foreach ($skill_categories as $cat): ?>
                    <?php if (in_array($cat, $categories)): ?>
                    <a href="brainpool.php?cat=<?php echo urlencode($cat); ?>" class="<?php echo (isset($_GET['cat']) && $_GET['cat']===$cat) ? 'active' : ''; ?>"><?php echo htmlspecialchars($cat); ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- COMPETENCES -->
            <div class="bp-card">
                <h3>🎓 Compétences proposées par la communauté</h3>
                <?php
                $filtered = $skills;
                if (!empty($_GET['cat'])) {
                    $filtered = array_filter($skills, fn($s) => $s['category'] === $_GET['cat']);
                }
                ?>
                <?php if (empty($filtered)): ?>
                    <div class="empty-state">Aucune compétence dans cette catégorie pour l'instant.<br>Sois le premier à proposer !</div>
                <?php else: ?>
                    <?php foreach ($filtered as $skill): ?>
                    <div class="skill-card">
                        <div class="skill-avatar">
                            <?php if (!empty($skill['avatar'])): ?>
                                <img src="<?php echo htmlspecialchars($skill['avatar']); ?>" alt="">
                            <?php else: ?>
                                <?php echo strtoupper(mb_substr($skill['prenom'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div class="skill-info">
                            <div class="skill-name"><?php echo htmlspecialchars($skill['skill_name']); ?> <span class="skill-tag"><?php echo htmlspecialchars($skill['category']); ?> <span class="level">★ <?php echo $skill['level']; ?></span></span></div>
                            <div class="skill-meta"><?php echo htmlspecialchars($skill['prenom'] . ' ' . $skill['nom']); ?> · <?php echo timeAgo($skill['created_at']); ?></div>
                            <?php if ($skill['description']): ?>
                                <div class="skill-desc"><?php echo htmlspecialchars($skill['description']); ?></div>
                            <?php endif; ?>
                        </div>
                        <form method="POST" action="request_help.php" style="flex-shrink:0;">
                            <input type="hidden" name="skill_id" value="<?php echo $skill['id']; ?>">
                            <input type="hidden" name="helper_id" value="<?php echo $skill['user_id']; ?>">
                            <button type="submit" class="bp-btn bp-btn-primary" style="padding:8px 16px;font-size:0.85rem;">Demander</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- SESSIONS EN COURS -->
            <?php if (!empty($active_sessions)): ?>
            <div class="bp-card">
                <h3>🔥 Sessions en cours</h3>
                <?php foreach ($active_sessions as $s): ?>
                    <div class="request-card accepted">
                        <strong><?php echo htmlspecialchars($s['skill_name']); ?></strong><br>
                        <span style="font-size:0.85rem;color:#666;">
                            <?php if ($s['requester_id'] == $my_id): ?>
                                Tu demandes de l'aide à <?php echo htmlspecialchars($s['hel_prenom'] . ' ' . $s['hel_nom']); ?>
                            <?php else: ?>
                                Tu aides <?php echo htmlspecialchars($s['req_prenom'] . ' ' . $s['req_nom']); ?>
                            <?php endif; ?>
                            · <?php echo $s['scheduled_at'] ? date('d/m/Y H:i', strtotime($s['scheduled_at'])) : 'Date non fixée'; ?>
                        </span>
                        <?php if ($s['helper_id'] == $my_id): ?>
                            <div class="request-actions">
                                <form method="POST" action="complete_session.php">
                                    <input type="hidden" name="request_id" value="<?php echo $s['id']; ?>">
                                    <button type="submit" class="bp-btn bp-btn-success" style="padding:8px 16px;font-size:0.85rem;">✅ Session terminée</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- MES DEMANDES -->
            <?php if (!empty($my_requests)): ?>
            <div class="bp-card">
                <h3>📋 Mon historique de demandes</h3>
                <?php foreach ($my_requests as $r): ?>
                    <div class="request-card <?php echo $r['status']; ?>">
                        <strong><?php echo htmlspecialchars($r['skill_name']); ?></strong> ·
                        <span style="font-size:0.8rem;text-transform:uppercase;font-weight:700;color:
                            <?php echo $r['status']==='pending'?'#F59E0B':($r['status']==='accepted'?'#3B82F6':'#10B981'); ?>">
                            <?php echo $r['status']; ?>
                        </span><br>
                        <span style="font-size:0.85rem;color:#666;">
                            <?php echo $r['helper_id'] ? 'Helper: ' . htmlspecialchars($r['prenom'] . ' ' . $r['nom']) : 'En attente d\'un helper'; ?>
                            · <?php echo $r['credits_offered']; ?> Crédit(s) H
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="bp-sidebar">
            <!-- DEMANDES RECUES -->
            <?php if (!empty($incoming_requests)): ?>
            <div class="bp-card">
                <h3>🔔 Demandes reçues</h3>
                <?php foreach ($incoming_requests as $r): ?>
                    <div class="request-card pending">
                        <strong><?php echo htmlspecialchars($r['requester_prenom'] . ' ' . $r['requester_nom']); ?></strong><br>
                        Demande : <strong><?php echo htmlspecialchars($r['skill_name']); ?></strong><br>
                        <span style="font-size:0.85rem;color:#666;"><?php echo htmlspecialchars($r['message'] ?: 'Pas de message'); ?></span><br>
                        <span style="font-size:0.8rem;color:#2E5961;font-weight:700;">💰 <?php echo $r['credits_offered']; ?> Crédit(s) H</span>
                        <div class="request-actions">
                            <a href="accept_request.php?id=<?php echo $r['id']; ?>" class="bp-btn bp-btn-success" style="padding:6px 14px;font-size:0.8rem;">✅ Accepter</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- MES COMPETENCES -->
            <div class="bp-card">
                <h3>🛠️ Mes compétences</h3>
                <?php if (empty($my_skills)): ?>
                    <div class="empty-state" style="padding:16px;">Tu n'as pas encore proposé de compétence.</div>
                <?php else: ?>
                    <?php foreach ($my_skills as $s): ?>
                        <div class="skill-tag"><?php echo htmlspecialchars($s['skill_name']); ?> <span class="level">★ <?php echo $s['level']; ?></span></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- LEADERBOARD -->
            <div class="bp-card">
                <h3>🏆 Top Helpers</h3>
                <?php $i=0; foreach ($leaderboard as $lb): $i++; ?>
                <div class="lb-item">
                    <div class="lb-rank <?php echo $i===1?'gold':($i===2?'silver':($i===3?'bronze':'')); ?>"><?php echo $i; ?></div>
                    <div style="flex:1;">
                        <div style="font-weight:600;font-size:0.9rem;"><?php echo htmlspecialchars($lb['prenom'] . ' ' . $lb['nom']); ?></div>
                        <div style="font-size:0.75rem;color:#888;"><?php echo $lb['total_earned']; ?> crédits gagnés</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Slider niveau
    var slider = document.querySelector('input[name="level"]');
    if (slider) {
        slider.oninput = function() { document.getElementById('level-val').textContent = this.value; };
    }

    // User menu
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

<style>.bpw-fab{display:none !important;}</style>
<?php include '../includes/brainpool_widget.php'; ?>

</body>
</html>
