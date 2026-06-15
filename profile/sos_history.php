
<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 19 Nov 1981 08:52:00 GMT");
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../persoinfo/signin.php');
    exit;
}

$my_id = (int)$_SESSION['id_user'];

// Crédits H
$stmt = $pdo->prepare("SELECT * FROM skill_credits WHERE user_id = ?");
$stmt->execute([$my_id]);
$credits = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$credits) {
    $pdo->prepare("INSERT INTO skill_credits (user_id, credits_balance) VALUES (?, 5)")->execute([$my_id]);
    $credits = ['credits_balance' => 5, 'total_earned' => 0, 'total_spent' => 0];
}

// Historique des demandes SOS avec infos du helper
$stmt = $pdo->prepare("
    SELECT sr.*, u.prenom as helper_prenom, u.nom as helper_nom, u.avatar as helper_avatar
    FROM skill_requests sr
    LEFT JOIN user u ON u.id_user = sr.helper_id
    WHERE sr.requester_id = ?
    ORDER BY sr.created_at DESC
");
$stmt->execute([$my_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status labels & colors
$statusConfig = [
    'pending'   => ['label' => 'En attente',   'color' => '#F59E0B', 'bg' => '#FFFBEB'],
    'accepted'  => ['label' => 'Acceptée',     'color' => '#3B82F6', 'bg' => '#EFF6FF'],
    'completed' => ['label' => 'Terminée',     'color' => '#10B981', 'bg' => '#ECFDF5'],
    'cancelled' => ['label' => 'Annulée',      'color' => '#6B7280', 'bg' => '#F3F4F6'],
];

function timeAgo($d) {
    $diff = time() - strtotime($d);
    if ($diff < 60) return "À l'instant";
    if ($diff < 3600) return floor($diff/60) . ' min';
    if ($diff < 86400) return floor($diff/3600) . ' h';
    if ($diff < 604800) return floor($diff/86400) . ' j';
    return date('d M Y', strtotime($d));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, post-check=0, pre-check=0" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <script>
(function(){
  window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
      window.location.replace(window.location.href);
    }
  });
  window.addEventListener('unload', function(){});
  fetch('../check_session.php', {cache: 'no-store'}).then(function(r){ return r.json(); }).then(function(data){
    if (!data.logged_in) {
      window.location.replace('../persoinfo/signin.php');
    }
  }).catch(function(){});
})();
  </script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique SOS - Student Hub</title>
    <link rel="stylesheet" href="../pages/home.css">
    <link rel="stylesheet" href="profile.css?v=5">
    <style>
        .hist-container { max-width: 900px; margin: 120px auto 40px; padding: 0 20px; }
        .hist-title { font-size: 2rem; color: #DC2626; margin-bottom: 4px; }
        .hist-sub { color: #666; font-size: 1rem; margin-bottom: 20px; }
        .credit-pill { display: inline-block; background: linear-gradient(135deg, #2E5961, #1a3a40); color: #fff; padding: 10px 24px; border-radius: 30px; font-weight: 700; margin-bottom: 24px; }
        .hist-count { font-size: 0.85rem; color: #888; margin-bottom: 16px; }
        .hist-grid { display: grid; gap: 14px; }
        .hist-card { background: #fff; border-radius: 16px; padding: 20px 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border-left: 5px solid #E5E7EB; transition: transform .15s, box-shadow .2s; }
        .hist-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.10); }
        .hist-card.pending   { border-left-color: #F59E0B; }
        .hist-card.accepted  { border-left-color: #3B82F6; }
        .hist-card.completed { border-left-color: #10B981; }
        .hist-card.cancelled { border-left-color: #6B7280; }
        .hist-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; }
        .hist-skill { font-size: 1.15rem; font-weight: 700; color: #1a2332; }
        .hist-status { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; padding: 4px 12px; border-radius: 999px; }
        .hist-body { color: #555; font-size: 0.9rem; line-height: 1.55; }
        .hist-body strong { color: #374151; }
        .hist-meta { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 12px; font-size: 0.8rem; color: #888; }
        .hist-meta span { display: flex; align-items: center; gap: 4px; }
        .hist-helper { display: flex; align-items: center; gap: 8px; margin-top: 10px; }
        .hist-avatar { width: 28px; height: 28px; border-radius: 50%; background: #2E5961; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem; overflow: hidden; }
        .hist-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .hist-empty { text-align: center; padding: 3rem 1rem; color: #888; }
        .hist-empty-icon { font-size: 3rem; margin-bottom: 12px; }
        .back-row { display: flex; gap: 12px; align-items: center; margin-bottom: 20px; flex-wrap: wrap; }
        .back-link { color: #2E5961; text-decoration: none; font-weight: 600; font-size: 0.9rem; }
        .back-link:hover { text-decoration: underline; }
        .btn-new { background: #DC2626; color: #fff; border: none; padding: 8px 18px; border-radius: 20px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; font-size: 0.85rem; }
        .btn-new:hover { background: #B91C1C; }
        .category-icon { margin-right: 4px; }
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
                <a href="../pages/settings.php" class="user-menu-item"><span class="user-menu-icon">⚙️</span> Settings</a>
                <a href="../pages/help.php" class="user-menu-item"><span class="user-menu-icon">❓</span> Help</a>
                <div class="user-menu-divider"></div>
                <a href="../logout.php" class="user-menu-item user-menu-logout"><span class="user-menu-icon">🚪</span> Log out</a>
            </div>
        </div>
    </nav>
</header>

<div class="hist-container">
    <div class="back-row">
        <a href="sos_examen.php" class="back-link">← Retour à SOS Examen</a>
        <a href="sos_examen.php" class="btn-new">+ Nouvelle demande</a>
    </div>

    <div class="hist-title">📋 Historique SOS</div>
    <div class="hist-sub">Toutes tes demandes d'aide en un coup d'œil</div>
    <div class="credit-pill">⚡ <?php echo (int)$credits['credits_balance']; ?> Crédits H disponibles</div>

    <div class="hist-count"><?php echo count($requests); ?> demande<?php echo count($requests)>1?'s':''; ?> au total</div>

    <?php if (empty($requests)): ?>
        <div class="hist-empty">
            <div class="hist-empty-icon">📭</div>
            <p>Tu n'as pas encore fait de demande SOS.</p>
            <p style="margin-top:6px;"><a href="sos_examen.php" class="btn-new">Demander de l'aide maintenant</a></p>
        </div>
    <?php else: ?>
        <div class="hist-grid">
            <?php foreach ($requests as $r):
                $st = $statusConfig[$r['status']] ?? $statusConfig['pending'];
                $catIcons = [
                    'Mathématiques'=>'📐','Physique'=>'⚡','Informatique'=>'💻','Chimie'=>'🧪',
                    'Langues'=>'🌍','Droit'=>'⚖️','Économie'=>'📈','Médecine'=>'🩺',
                    'Design'=>'🎨','Marketing'=>'📢','Musique'=>'🎵','Cuisine'=>'🍳','Sport'=>'⚽','Autre'=>'⭐'
                ];
            ?>
                <div class="hist-card <?php echo htmlspecialchars($r['status']); ?>">
                    <div class="hist-header">
                        <div class="hist-skill">
                            <span class="category-icon"><?php echo $catIcons[$r['category']] ?? '📚'; ?></span>
                            <?php echo htmlspecialchars($r['skill_name']); ?>
                        </div>
                        <span class="hist-status" style="background:<?php echo $st['bg']; ?>;color:<?php echo $st['color']; ?>;">
                            <?php echo $st['label']; ?>
                        </span>
                    </div>

                    <div class="hist-body">
                        <?php if (!empty($r['message'])): ?>
                            <p style="margin:0 0 8px;"><?php echo nl2br(htmlspecialchars($r['message'])); ?></p>
                        <?php endif; ?>
                        <p style="margin:0;">
                            <strong>Matière :</strong> <?php echo htmlspecialchars($r['category']); ?> ·
                            <strong>Crédits offerts :</strong> <?php echo (int)$r['credits_offered']; ?>H
                        </p>
                    </div>

                    <?php if (!empty($r['helper_prenom'])): ?>
                        <div class="hist-helper">
                            <div class="hist-avatar">
                                <?php if (!empty($r['helper_avatar'])): ?>
                                    <img src="<?php echo htmlspecialchars($r['helper_avatar']); ?>" alt="">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($r['helper_prenom'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <span style="font-size:0.82rem;color:#555;">
                                Helper : <strong><?php echo htmlspecialchars($r['helper_prenom'] . ' ' . $r['helper_nom']); ?></strong>
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="hist-meta">
                        <span>🕐 <?php echo timeAgo($r['created_at']); ?></span>
                        <?php if ($r['status'] === 'pending'): ?>
                            <span>⏳ En attente d'un helper</span>
                        <?php elseif ($r['status'] === 'accepted'): ?>
                            <span style="color:#3B82F6;">✅ Helper trouvé</span>
                        <?php elseif ($r['status'] === 'completed'): ?>
                            <span style="color:#10B981;">🎉 Session terminée</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
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
