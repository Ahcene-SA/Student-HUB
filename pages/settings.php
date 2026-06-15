<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 19 Nov 1981 08:52:00 GMT");
$user_id = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;

if ($user_id === 0) {
    header("Location: ../persoinfo/signin.php");
    exit();
}

require_once __DIR__ . '/../includes/db_config.php';
$conn = get_db_connection();
$stmt = $conn->prepare("SELECT * FROM user WHERE id_user = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$msg = '';
$msgType = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $filliere = trim($_POST['filliere'] ?? '');
    $universite = trim($_POST['universite'] ?? '');
    $niveau = trim($_POST['niveau'] ?? '');
    $promotion = trim($_POST['promotion'] ?? '');
    $specialite = trim($_POST['specialite'] ?? '');
    $instagram = trim($_POST['instagram'] ?? '');
    $linkedin = trim($_POST['linkedin'] ?? '');
    $facebook = trim($_POST['facebook'] ?? '');

    $upd = $conn->prepare("UPDATE user SET prenom=?, nom=?, username=?, email=?, phone=?, filliere=?, universite=?, niveau=?, promotion=?, specialite=?, instagram=?, linkedin=?, facebook=? WHERE id_user=?");
    $upd->bind_param("sssssssssssssi", $prenom, $nom, $username, $email, $phone, $filliere, $universite, $niveau, $promotion, $specialite, $instagram, $linkedin, $facebook, $user_id);
    if ($upd->execute()) {
        $msg = 'Profil mis à jour avec succès !';
        $msgType = 'success';
        // Refresh user data
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
    } else {
        $msg = 'Erreur lors de la mise à jour.';
        $msgType = 'error';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (password_verify($current, $user['mdp'] ?? '')) {
        if ($new === $confirm && strlen($new) >= 6) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE user SET mdp=? WHERE id_user=?");
            $upd->bind_param("si", $hash, $user_id);
            $upd->execute();
            $msg = 'Mot de passe changé avec succès !';
            $msgType = 'success';
        } else {
            $msg = 'Les mots de passe ne correspondent pas ou sont trop courts (min 6 caractères).';
            $msgType = 'error';
        }
    } else {
        $msg = 'Mot de passe actuel incorrect.';
        $msgType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student HUB – Paramètres</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="settings.css?v=1" />
</head>
<body>
  <div class="main_page">

    <!-- NAVBAR -->
    <header class="home_navbar">
      <div class="leftnav">
        <div class="nav_left">
          <img src="../logo/Student_HUB_LOGO.png" alt="Student HUB" class="logo" />
        </div>
        <div class="nav_center">
          <input type="text" placeholder="Search..." class="search_input" />
        </div>
      </div>
      <nav class="nav_right">
        <a href="home.php">Home</a>
        <a href="immobilier.php">Immobilier</a>
        <a href="stage.php">Stage</a>
        <a href="events.php">Events</a>
        <a href="mentoring.php">Mentoring</a>
        <a href="bonplan.php">Bons plans</a>
        <div class="user-menu">
          <button class="post_btn user-menu-trigger" tabindex="0">Mon compte ▾</button>
          <div class="user-menu-dropdown">
            <a href="../profile/profile.php?id=<?php echo $user_id; ?>" class="user-menu-item">
              <span class="user-menu-icon">👤</span> Profile
            </a>
            <a href="messages.php" class="user-menu-item">
              <span class="user-menu-icon">💬</span> Messages
            </a>
            <a href="settings.php" class="user-menu-item">
              <span class="user-menu-icon">⚙️</span> Settings
            </a>
            <a href="help.php" class="user-menu-item">
              <span class="user-menu-icon">❓</span> Help
            </a>
            <div class="user-menu-divider"></div>
            <a href="../logout.php" class="user-menu-item user-menu-logout">
              <span class="user-menu-icon">🚪</span> Log out
            </a>
          </div>
        </div>
      </nav>
    </header>

    <!-- MAIN CONTENT -->
    <main class="settings-layout">
      <div class="settings-card">
        <h1>⚙️ Paramètres</h1>

        <?php if ($msg): ?>
          <div class="alert alert-<?php echo $msgType; ?>"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <!-- PROFILE INFO -->
        <section class="settings-section">
          <h2>👤 Informations personnelles</h2>
          <form method="POST" action="">
            <div class="form-grid">
              <div class="form-group">
                <label>Prénom</label>
                <input type="text" name="prenom" value="<?php echo htmlspecialchars($user['prenom'] ?? ''); ?>" />
              </div>
              <div class="form-group">
                <label>Nom</label>
                <input type="text" name="nom" value="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>" />
              </div>
              <div class="form-group">
                <label>Nom d'utilisateur</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" />
              </div>
              <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" />
              </div>
              <div class="form-group">
                <label>Téléphone</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" />
              </div>
              <div class="form-group">
                <label>Filière</label>
                <input type="text" name="filliere" value="<?php echo htmlspecialchars($user['filliere'] ?? ''); ?>" />
              </div>
              <div class="form-group">
                <label>Université</label>
                <input type="text" name="universite" value="<?php echo htmlspecialchars($user['universite'] ?? ''); ?>" />
              </div>
              <div class="form-group">
                <label>Niveau</label>
                <input type="text" name="niveau" value="<?php echo htmlspecialchars($user['niveau'] ?? ''); ?>" />
              </div>
              <div class="form-group">
                <label>Promotion</label>
                <input type="text" name="promotion" value="<?php echo htmlspecialchars($user['promotion'] ?? ''); ?>" />
              </div>
              <div class="form-group">
                <label>Spécialité</label>
                <input type="text" name="specialite" value="<?php echo htmlspecialchars($user['specialite'] ?? ''); ?>" />
              </div>
            </div>

            <h3 style="margin-top:24px;font-size:1rem;color:var(--brand);">🌐 Réseaux sociaux</h3>
            <div class="form-grid">
              <div class="form-group">
                <label>Instagram</label>
                <input type="text" name="instagram" value="<?php echo htmlspecialchars($user['instagram'] ?? ''); ?>" placeholder="https://instagram.com/..." />
              </div>
              <div class="form-group">
                <label>LinkedIn</label>
                <input type="text" name="linkedin" value="<?php echo htmlspecialchars($user['linkedin'] ?? ''); ?>" placeholder="https://linkedin.com/in/..." />
              </div>
              <div class="form-group">
                <label>Facebook</label>
                <input type="text" name="facebook" value="<?php echo htmlspecialchars($user['facebook'] ?? ''); ?>" placeholder="https://facebook.com/..." />
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" name="update_profile" class="save-btn">💾 Sauvegarder les modifications</button>
            </div>
          </form>
        </section>

        <!-- SECURITY -->
        <section class="settings-section">
          <h2>🔒 Sécurité</h2>
          <form method="POST" action="">
            <div class="form-grid">
              <div class="form-group">
                <label>Mot de passe actuel</label>
                <input type="password" name="current_password" placeholder="••••••••" />
              </div>
              <div class="form-group">
                <label>Nouveau mot de passe</label>
                <input type="password" name="new_password" placeholder="Min. 6 caractères" />
              </div>
              <div class="form-group">
                <label>Confirmer le nouveau mot de passe</label>
                <input type="password" name="confirm_password" placeholder="••••••••" />
              </div>
            </div>
            <div class="form-actions">
              <button type="submit" name="change_password" class="save-btn">🔐 Changer le mot de passe</button>
            </div>
          </form>
        </section>

        <!-- ACCOUNT -->
        <section class="settings-section danger-zone">
          <h2>⚠️ Zone danger</h2>
          <p style="color:#666;margin-bottom:12px;">Ces actions sont irréversibles. Fais attention !</p>
          <a href="../profile/profile.php?id=<?php echo $user_id; ?>" class="secondary-btn">Voir mon profil</a>
        </section>
      </div>
    </main>
  </div>

<script>
(function(){
  var btns = document.querySelectorAll(".user-menu-trigger");
  for (var i = 0; i < btns.length; i++) {
    btns[i].addEventListener("click", function(e) {
      e.stopPropagation();
      var menu = this.closest(".user-menu");
      var dd = menu.querySelector(".user-menu-dropdown");
      if (dd) {
        var isOpen = dd.style.display === "block";
        dd.style.display = isOpen ? "none" : "block";
        dd.style.opacity = isOpen ? "0" : "1";
        dd.style.pointerEvents = isOpen ? "none" : "all";
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
})();
</script>

</body>
</html>
