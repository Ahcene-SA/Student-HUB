<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 19 Nov 1981 08:52:00 GMT");

require 'db.php';

// Auto-create follows table if it doesn't exist (no FK to avoid engine issues)
$pdo->exec("
    CREATE TABLE IF NOT EXISTS follows (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        follower_id  INT NOT NULL,
        following_id INT NOT NULL,
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_follow (follower_id, following_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Auto-create posts table if it doesn't exist
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
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_category (category),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Migrate old posts table (add columns if missing)
try { $pdo->exec("ALTER TABLE posts ADD COLUMN price VARCHAR(50)"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN location VARCHAR(255)"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN event_date VARCHAR(50)"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN company VARCHAR(255)"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN chambres INT"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN meuble VARCHAR(20)"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN type_event VARCHAR(50)"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN tarif VARCHAR(50)"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN is_free VARCHAR(10)"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN domaine VARCHAR(100)"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN duree VARCHAR(50)"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN type_stage VARCHAR(30)"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN niveau_etude VARCHAR(50)"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN matiere VARCHAR(100)"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN niveau_mentoring VARCHAR(50)"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN langue VARCHAR(50)"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN disponibilite VARCHAR(50)"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN is_mentor VARCHAR(10)"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN prix_mentoring VARCHAR(50)"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN produit VARCHAR(255)"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN etat VARCHAR(50)"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE posts ADD COLUMN bonplan_category VARCHAR(50)"); } catch (PDOException $e) {}

// Auto-create user_sections table if it doesn't exist
$pdo->exec("
    CREATE TABLE IF NOT EXISTS user_sections (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        user_id     INT NOT NULL,
        type        VARCHAR(30) NOT NULL,
        title       VARCHAR(255),
        description VARCHAR(150),
        date_value  VARCHAR(50),
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_type (type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Migrate old schema (add columns if missing)
try {
    $pdo->exec("ALTER TABLE user_sections ADD COLUMN title VARCHAR(255), ADD COLUMN description VARCHAR(150), ADD COLUMN date_value VARCHAR(50)");
} catch (PDOException $e) {
    // Columns likely already exist
}

// Determine whose profile we are viewing
$my_id = isset($_SESSION['id_user']) ? (int) $_SESSION['id_user'] : 0;
$profile_id = isset($_GET['id']) ? (int) $_GET['id'] : $my_id;

// If no one is logged in and no ID provided, send to login
if ($my_id === 0 && $profile_id === 0) {
    header("Location: ../persoinfo/signin.php");
    exit();
}

// If no ID in URL and user IS logged in, default to their own profile
if ($profile_id === 0) {
    $profile_id = $my_id;
}

// Fetch profile user's data
$stmt = $pdo->prepare("SELECT * FROM user WHERE id_user = ?");
$stmt->execute([$profile_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If user doesn't exist, redirect to own profile
if (!$user) {
    header("Location: profile.php" . ($my_id ? "?id=" . $my_id : ""));
    exit();
}

// Is this the logged-in user's own profile?
$is_my_profile = ($profile_id === $my_id && $my_id !== 0);

// Check if current user is following this profile
$is_following = false;
if (!$is_my_profile && $my_id !== 0) {
    $stmt = $pdo->prepare("
        SELECT id FROM follows
        WHERE follower_id = ? AND following_id = ?
    ");
    $stmt->execute([$my_id, $profile_id]);
    $is_following = (bool) $stmt->fetch();
}

// Count followers and following for this profile
$stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
$stmt->execute([$profile_id]);
$followers_count = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
$stmt->execute([$profile_id]);
$following_count = (int) $stmt->fetchColumn();

/* Fetch user's general posts */
$stmt = $pdo->prepare("
    SELECT * FROM posts
    WHERE user_id = ? AND category = 'general'
    ORDER BY created_at DESC
");
$stmt->execute([$profile_id]);
$user_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

function timeAgoProfile($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    if ($diff < 60) return 'À l\'instant';
    if ($diff < 3600) return floor($diff / 60) . ' min';
    if ($diff < 86400) return floor($diff / 3600) . ' h';
    if ($diff < 604800) return floor($diff / 86400) . ' j';
    return date('d M Y', $time);
}

/* Fetch right-column sections */
$stmt = $pdo->prepare("SELECT * FROM user_sections WHERE user_id = ? AND type = 'experience' ORDER BY created_at DESC");
$stmt->execute([$profile_id]);
$experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM user_sections WHERE user_id = ? AND type = 'certificate' ORDER BY created_at DESC");
$stmt->execute([$profile_id]);
$certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM user_sections WHERE user_id = ? AND type = 'education' ORDER BY created_at DESC");
$stmt->execute([$profile_id]);
$educations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
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
    <title>Student Hub - Profile</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="profile.css">
    <style>
      /* Critical bubble + modal styles — fallback if external CSS is cached */
      .post-bubble-wrapper {
        position: fixed; bottom: 28px; right: 28px; z-index: 1000;
        display: block; width: 56px; height: 56px;
      }
      .bubbles-container {
        position: absolute; bottom: calc(100% + 16px);
        right: 0; left: 0; z-index: 1001;
        display: flex; flex-direction: column-reverse;
        align-items: center; gap: 12px;
      }
      .bubble {
        width: 56px; height: 56px; border-radius: 50%;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        text-decoration: none; cursor: pointer; border: none; background: none; padding: 0;
        box-shadow: 0 8px 24px rgba(0,0,0,0.18);
        opacity: 0; transform: translateY(30px) scale(0.5);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.25s ease;
        pointer-events: none;
      }
      .bubble.visible {
        opacity: 1; transform: translateY(0) scale(1);
        pointer-events: all;
      }
      .bubble img {
        width: 24px; height: 24px;
        filter: brightness(0) invert(1);
        margin-bottom: 2px; display: block;
      }
      .bubble span {
        font-size: 0.55rem; font-weight: 700;
        color: white; font-family: 'ROBOTO', sans-serif;
        white-space: nowrap;
      }
      .bubble-immo     { background: linear-gradient(135deg, #8B5E3C, #D4A574); }
      .bubble-events   { background: linear-gradient(135deg, #FF3CAC, #784BA0); }
      .bubble-stage    { background: linear-gradient(135deg, #1A56DB, #2563EB); }
      .bubble-mentoring{ background: linear-gradient(135deg, #166534, #15803D); }
      .bubble-bonplan  { background: linear-gradient(135deg, #F59E0B, #D97706); }
      .bubble-add      {
        background: transparent;
        border: 2px dashed rgba(46,89,97,0.4);
      }
      .bubble-add span { color: #2E5961; font-size: 1.4rem; font-weight: 300; }
      /* Modal fallback */
      .post-modal {
        position: fixed; inset: 0; z-index: 2000;
        display: flex; align-items: center; justify-content: center;
        opacity: 0; pointer-events: none;
        transition: opacity 0.25s ease;
      }
      .post-modal.active { opacity: 1; pointer-events: all; }
      .modal-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.45); }
      .modal-card {
        position: relative; background: #fff; border-radius: 16px;
        width: 100%; max-width: 520px; margin: 20px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        overflow: hidden;
        transform: scale(0.92) translateY(20px);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
      }
      .post-modal.active .modal-card { transform: scale(1) translateY(0); }
      .modal-header {
        padding: 18px 22px;
        display: flex; justify-content: space-between; align-items: center; color: #fff;
      }
      .modal-header h3 { color: #fff; font-size: 1.1rem; margin: 0; }
      .modal-close {
        background: none; border: none; color: #fff;
        font-size: 1.6rem; cursor: pointer; line-height: 1;
      }
      .modal-body { padding: 22px; }
      .modal-input, .modal-textarea {
        width: 100%; border: 1.5px solid #D1D5DB; border-radius: 12px;
        padding: 12px 14px; font-family: 'ROBOTO', sans-serif; font-size: 0.95rem;
        margin-bottom: 14px; outline: none; box-sizing: border-box;
      }
      .modal-textarea { min-height: 120px; resize: vertical; }
      .modal-input[type="file"] {
        width: 100%; padding: 10px 14px; border: 1.5px solid #D1D5DB; border-radius: 12px;
        background: #ffffff; font-family: 'ROBOTO', sans-serif; font-size: 0.9rem;
        color: #374151; cursor: pointer; outline: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
      }
      .modal-input[type="file"]:focus {
        border-color: #2E5961; box-shadow: 0 0 0 3px rgba(46,89,97,0.12);
      }
      .modal-input[type="file"]::file-selector-button {
        background: linear-gradient(90deg, #2E5961, #3e7b86); color: #ffffff; border: none;
        border-radius: 999px; padding: 8px 16px; font-family: 'ROBOTO', sans-serif;
        font-size: 0.85rem; font-weight: 700; cursor: pointer; transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(46,89,97,0.25); margin-right: 12px;
      }
      .modal-input[type="file"]:hover::file-selector-button {
        transform: translateY(-2px); box-shadow: 0 6px 16px rgba(46,89,97,0.35);
      }
      .modal-submit {
        width: 100%; padding: 12px; border: none; border-radius: 12px;
        color: #fff; font-family: 'ROBOTO', sans-serif;
        font-weight: 700; font-size: 1rem; cursor: pointer;
      }
      .immo { background: linear-gradient(135deg, #8B5E3C, #D4A574); }
      .events { background: linear-gradient(135deg, #FF3CAC, #784BA0); }
      .stage { background: linear-gradient(135deg, #1A56DB, #2563EB); }
      .mentoring { background: linear-gradient(135deg, #166534, #15803D); }
      .bonplan { background: linear-gradient(135deg, #F59E0B, #D97706); }
      /* ── Modal row for side-by-side inputs ── */
      .modal-row {
        display: flex;
        gap: 12px;
        margin-bottom: 14px;
      }
      .modal-half {
        flex: 1;
        margin-bottom: 0 !important;
      }
      .modal-label {
        display: block;
        font-size: 0.85rem;
        color: #6b7280;
        margin-bottom: 6px;
        font-family: 'ROBOTO', sans-serif;
      }
      /* ── Modal select dropdown ── */
      .modal-select {
        width: 100%;
        height: 46px;
        border: 1.5px solid #D1D5DB;
        border-radius: 12px;
        padding: 0 14px;
        font-family: 'ROBOTO', sans-serif;
        font-size: 0.95rem;
        margin-bottom: 14px;
        outline: none;
        box-sizing: border-box;
        background: white;
        cursor: pointer;
        color: #1f2b37;
        transition: border-color 0.2s ease;
      }
      .modal-select:focus {
        border-color: #2E5961;
        box-shadow: 0 0 0 3px rgba(46,89,97,0.12);
      }
      /* ── Modal checkbox row for tarif ── */
      .modal-row-tarif {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 14px;
      }
      .modal-row-tarif > .modal-input {
        flex: 1;
        margin-bottom: 0;
      }
      .modal-check-label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-family: 'ROBOTO', sans-serif;
        font-size: 0.95rem;
        color: #1f2b37;
        white-space: nowrap;
      }
      .modal-check-label input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #2E5961;
      }
    </style>
</head>

<body>
<div class="profilepage">

  <?php if (!empty($_SESSION['upload_errors'])): ?>
    <div class="alert alert-danger" style="margin: 1rem auto; max-width: 900px;">
      <?php foreach ($_SESSION['upload_errors'] as $err): ?>
        <p style="margin:0;"><?php echo htmlspecialchars($err); ?></p>
      <?php endforeach; ?>
    </div>
    <?php unset($_SESSION['upload_errors']); ?>
  <?php endif; ?>

  <?php if (!empty($_SESSION['upload_success'])): ?>
    <div class="alert alert-success" style="margin: 1rem auto; max-width: 900px;">
      <?php echo htmlspecialchars($_SESSION['upload_success']); ?>
    </div>
    <?php unset($_SESSION['upload_success']); ?>
  <?php endif; ?>

 
    <header class="home_navbar">

        <div class="leftnav">
            <div class="nav_left">
        <img src="../logo/Student_HUB_LOGO.png" alt="Student HUB" class="logo">
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
          <button class="post_btn user-menu-trigger" tabindex="0">
            Mon compte ▾
          </button>
          <div class="user-menu-dropdown">
            <a href="profile.php?id=<?php echo $my_id; ?>" class="user-menu-item">
              <span class="user-menu-icon">👤</span> Profile
            </a>
            <a href="../pages/messages.php" class="user-menu-item">
              <span class="user-menu-icon">💬</span> Messages
            </a>
            <a href="../pages/settings.php" class="user-menu-item">
              <span class="user-menu-icon">⚙️</span> Settings
            </a>
            <a href="../pages/help.php" class="user-menu-item">
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
  <div class="main-content">

    <!-- LEFT COLUMN -->
    <div class="left-column">

      <!-- Top block: profile banner + avatar -->
      <div class="profile-banner">
        <div class="banner-top" style="position:relative;">
          <?php if (!empty($user['banner'])): ?>
            <img src="<?php echo htmlspecialchars($user['banner']); ?>" alt="" id="banner-img">
          <?php endif; ?>
          <?php if ($is_my_profile): ?>
            <label for="banner-upload" class="banner-edit-overlay">📷 <?php echo empty($user['banner']) ? 'Ajouter une photo' : 'Changer la photo'; ?></label>
          <?php endif; ?>
        </div>
        <div class="avatar" style="position:relative;">
          <?php if (!empty($user['avatar'])): ?>
            <span class="avatar-placeholder"><img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="" id="avatar-img"></span>
          <?php else: ?>
            <span class="avatar-initials"><?php echo htmlspecialchars(strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1))); ?></span>
          <?php endif; ?>
          <?php if ($is_my_profile): ?>
            <label for="avatar-upload" class="avatar-edit-overlay">📷</label>
          <?php endif; ?>
        </div>

        <?php if ($is_my_profile): ?>
        <!-- Hidden quick-upload form — submits instantly when a photo is picked -->
        <form id="photo-upload-form" method="POST" action="upload_photo.php?id=<?php echo $profile_id; ?>" enctype="multipart/form-data" style="display:none;">
          <input type="file" name="banner" id="banner-upload" accept="image/*" onchange="this.form.submit();">
          <input type="file" name="avatar" id="avatar-upload" accept="image/*" onchange="this.form.submit();">
        </form>
        <?php endif; ?>

        <div class="profile-info">

          <!-- VIEW MODE -->
          <div id="banner-view">
            <h2><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h2>
            <h6><?php echo '@' . htmlspecialchars($user['username']); ?></h6>
            <p><?php echo htmlspecialchars($user['filliere'] ?? 'Filière'); ?> · <?php echo htmlspecialchars($user['niveau'] ?? '2026'); ?> · <?php echo htmlspecialchars($user['universite'] ?? $user['school'] ?? 'Université'); ?></p>
            <div class="contact">
               <a href="<?php echo htmlspecialchars($user['instagram'] ?? '#'); ?>"><img src="../mainpage/image/svg/instagram-svgrepo-com.svg" alt=""></a>
               <a href="<?php echo htmlspecialchars($user['linkedin'] ?? '#'); ?>"><img src="../mainpage/image/svg/linkedin-svgrepo-com.svg" alt=""></a>
               <a href="<?php echo htmlspecialchars($user['facebook'] ?? '#'); ?>"><img src="../mainpage/image/svg/facebook-svgrepo-com.svg" alt=""></a>
            </div>
            <?php if ($is_my_profile): ?>
              <button type="button" class="left-edit-btn" onclick="toggleBannerEdit()" style="margin-top:10px;">✏️ Edit</button>
            <?php endif; ?>
          </div>

          <!-- EDIT MODE (only for owner) — text fields only, photos are changed via overlays -->
          <form id="banner-edit" style="display:none" method="POST" action="update_banner.php?id=<?php echo $profile_id; ?>">
            <div class="edit-field">
              <label>Filière</label>
              <input type="text" name="filliere" value="<?php echo htmlspecialchars($user['filliere'] ?? ''); ?>" placeholder="Filière">
            </div>
            <div class="edit-field">
              <label>Université</label>
              <input type="text" name="universite" value="<?php echo htmlspecialchars($user['universite'] ?? $user['school'] ?? ''); ?>" placeholder="Université">
            </div>
            <div class="edit-field">
              <label>Niveau</label>
              <input type="text" name="niveau" value="<?php echo htmlspecialchars($user['niveau'] ?? ''); ?>" placeholder="Niveau">
            </div>
            <div class="edit-field">
              <label>Instagram URL</label>
              <input type="text" name="instagram" value="<?php echo htmlspecialchars($user['instagram'] ?? ''); ?>" placeholder="https://instagram.com/...">
            </div>
            <div class="edit-field">
              <label>LinkedIn URL</label>
              <input type="text" name="linkedin" value="<?php echo htmlspecialchars($user['linkedin'] ?? ''); ?>" placeholder="https://linkedin.com/in/...">
            </div>
            <div class="edit-field">
              <label>Facebook URL</label>
              <input type="text" name="facebook" value="<?php echo htmlspecialchars($user['facebook'] ?? ''); ?>" placeholder="https://facebook.com/...">
            </div>
            <div class="edit-actions">
              <input type="submit" name="save_banner" value="Sauvegarder" class="save-btn">
              <button type="button" class="cancel-btn" onclick="toggleBannerEdit()">Annuler</button>
            </div>
          </form>

          <div class="fol-mes">
            <?php if (!$is_my_profile): ?>
              <div class="follow">
                <?php if ($is_following): ?>
                  <form method="POST" action="unfollow.php" style="display:inline;">
                    <input type="hidden" name="user_id" value="<?php echo $profile_id; ?>">
                    <button type="submit" class="follow-btn following">✓ Following (<?php echo $followers_count; ?>)</button>
                  </form>
                <?php else: ?>
                  <form method="POST" action="follow.php" style="display:inline;">
                    <input type="hidden" name="user_id" value="<?php echo $profile_id; ?>">
                    <button type="submit" class="follow-btn">FOLLOW (<?php echo $followers_count; ?>)</button>
                  </form>
                <?php endif; ?>
              </div>
              <div class="message">
                <button type="button" class="mes-but" onclick="toggleProfileChat()"><img src="img/message-circle-dots-svgrepo-com.svg" alt="" class="message-img"></button>
                <div class="chat-tab" id="profile-chat-tab">
                  <div class="chat-header">
                    <span>💬 Message</span>
                    <button type="button" onclick="toggleProfileChat()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#fff;">✕</button>
                  </div>
                  <div class="chat-body" id="profile-chat-body">
                    <p class="chat-placeholder">Chargement...</p>
                  </div>
                  <div class="chat-input-row">
                    <input type="text" id="profile-chat-input" placeholder="Écrire un message..." class="chat-input" onkeydown="if(event.key==='Enter') sendProfileChatMessage()">
                    <button class="chat-send" onclick="sendProfileChatMessage()">➤</button>
                  </div>
                </div>
              </div>
            <?php else: ?>
              <!-- Own profile: show follower stats -->
              <div class="profile-stats" style="display:flex; gap:1.5rem; margin-top:10px; font-size:0.9rem; color:#666;">
                <span><strong><?php echo $followers_count; ?></strong> followers</span>
                <span><strong><?php echo $following_count; ?></strong> following</span>
              </div>
            <?php endif; ?>
          </div>

        </div>
      </div>

      <!-- Bottom block: details -->
      <div class="info-block">
        <div class="info-block-header">
          <h3>Informations</h3>
          <?php if ($is_my_profile): ?>
            <button type="button" class="left-edit-btn" id="left-edit-btn" onclick="toggleLeftEdit()">✏️ Edit</button>
          <?php endif; ?>
        </div>
        <!-- VIEW MODE -->
        <div id="info-view">
          <div class="info-row"><span>Email</span><span><?php echo htmlspecialchars($user['email'] ?? '—'); ?></span></div>
          <div class="info-row"><span>Téléphone</span><span><?php echo htmlspecialchars($user['phone'] ?? '—'); ?></span></div>
          <div class="info-row"><span>Promotion</span><span><?php echo htmlspecialchars($user['promotion'] ?? '—'); ?></span></div>
          <div class="info-row"><span>Spécialité</span><span><?php echo htmlspecialchars($user['specialite'] ?? '—'); ?></span></div>
          <div class="info-row"><span>Niveau</span><span><?php echo htmlspecialchars($user['niveau'] ?? '—'); ?></span></div>
        </div>
        <!-- EDIT MODE (only for owner) -->
        <form id="info-edit" style="display:none" method="POST" action="update_info.php">
          <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
          <div class="edit-field">
            <label for="phone">Téléphone</label>
            <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Téléphone">
          </div>
          <div class="edit-field">
            <label for="promotion">Promotion</label>
            <input type="text" name="promotion" id="promotion" value="<?php echo htmlspecialchars($user['promotion'] ?? ''); ?>" placeholder="Promotion">
          </div>
          <div class="edit-field">
            <label for="specialite">Spécialité</label>
            <input type="text" name="specialite" id="specialite" value="<?php echo htmlspecialchars($user['specialite'] ?? ''); ?>" placeholder="Spécialité">
          </div>
          <div class="edit-field">
            <label for="niveau">Niveau</label>
            <input type="text" name="niveau" id="niveau" value="<?php echo htmlspecialchars($user['niveau'] ?? ''); ?>" placeholder="Niveau">
          </div>
          <div class="edit-actions">
            <input type="submit" name="save_info" value="Sauvegarder" class="save-btn">
            <button type="button" class="cancel-btn" onclick="toggleLeftEdit()">Annuler</button>
          </div>
        </form>
      </div>
      <!-- Posts -->
<div class="posts-block">
    <h3><?php echo $is_my_profile ? 'Mes Publications' : 'Publications'; ?></h3>

    <?php if (empty($user_posts)): ?>
      <p class="empty-section" style="text-align:center;padding:1.5rem;">— Aucune publication pour le moment —</p>
    <?php else: ?>
      <?php foreach ($user_posts as $post): ?>
        <div class="post-card">
          <div class="post-header">
            <span class="post-author"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></span>
            <span class="post-date"><?php echo timeAgoProfile($post['created_at']); ?></span>
          </div>
          <?php if (!empty($post['title'])): ?>
            <h4 style="margin:0 0 8px;font-size:1rem;font-weight:700;color:#1a2332;"><?php echo htmlspecialchars($post['title']); ?></h4>
          <?php endif; ?>
          <p class="post-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
          <?php if (!empty($post['image'])): ?>
            <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="" class="img-post">
          <?php endif; ?>
          <div class="post-footer">
            <span>Like 0</span>
            <span>0 comments</span>
          </div>
          <?php if ($is_my_profile): ?>
            <form method="POST" action="delete_post.php" style="margin-top:0.5rem;text-align:right;" onsubmit="return confirm('Delete this post?');">
              <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>" />
              <button type="submit" style="background:none;border:none;color:#EF4444;cursor:pointer;font-size:0.8rem;padding:0;">🗑️ Delete</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
</div>
    </div>

    <!-- RIGHT COLUMN -->
    <div class="right-column">
      <div class="right-col-header">
        <?php if ($is_my_profile): ?>
          <button type="button" class="right-edit-btn" id="right-edit-btn" onclick="toggleRightEdit()">✏️ Edit</button>
        <?php endif; ?>
      </div>

      <!-- EDIT PANEL -->
      <div class="edit-panel" id="edit-panel" style="display:none">
        <div class="edit-select-wrap">
          <label for="section-select">Choisir une section :</label>
          <select id="section-select" class="edit-select" onchange="showSectionInput(this.value)">
            <option value="" disabled selected>Sélectionner...</option>
            <option value="experience">💼 Experience</option>
            <option value="certificate">🏆 Certificates</option>
            <option value="education">🎓 Education</option>
          </select>
        </div>
        <div class="edit-input-wrap" id="edit-input-wrap" style="display:none">
          <form method="POST" action="add_section.php" class="edit-form">
            <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
            <input type="hidden" name="type" id="section-type">
            <input type="text" name="title" placeholder="Titre..." class="edit-text-input" required>
            <textarea name="description" placeholder="Description (max 150 caractères)..." class="edit-text-input" maxlength="150" rows="2" required></textarea>
            <input type="text" name="date_value" placeholder="Date (ex: 2024, Jan 2024...)" class="edit-text-input" required>
            <input type="submit" name="add_section" value="Ajouter" class="edit-submit-btn">
          </form>
        </div>
        <button type="button" class="cancel-btn" onclick="toggleRightEdit()">Fermer</button>
      </div>

      <!-- EXPERIENCE -->
      <div class="experience">
        <h2>Experience:</h2>
        <?php if (empty($experiences)): ?>
          <p class="empty-section">— Aucune expérience ajoutée —</p>
        <?php else: ?>
          <?php foreach($experiences as $exp): ?>
            <div class="activity-item">
              <div class="activity-dot"></div>
              <div class="activity-content">
                <div class="activity-title"><?php echo htmlspecialchars($exp['title'] ?? $exp['content'] ?? ''); ?></div>
                <?php if (!empty($exp['date_value']) || !empty($exp['description'])): ?>
                  <div class="activity-meta">
                    <?php if (!empty($exp['description'])): ?>
                      <span class="activity-desc"><?php echo htmlspecialchars($exp['description']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($exp['date_value'])): ?>
                      <span class="activity-date"><?php echo htmlspecialchars($exp['date_value']); ?></span>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
              <?php if ($is_my_profile): ?>
                <form method="POST" action="delete_section.php" class="delete-form">
                  <input type="hidden" name="id" value="<?php echo $exp['id']; ?>">
                  <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                  <button type="submit" class="delete-item-btn">✕</button>
                </form>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- CERTIFICATES -->
      <div class="certificates">
        <h2>Certificates:</h2>
        <?php if (empty($certificates)): ?>
          <p class="empty-section">— Aucun certificat ajouté —</p>
        <?php else: ?>
          <?php foreach($certificates as $cert): ?>
            <div class="activity-item">
              <div class="activity-dot"></div>
              <div class="activity-content">
                <div class="activity-title"><?php echo htmlspecialchars($cert['title'] ?? $cert['content'] ?? ''); ?></div>
                <?php if (!empty($cert['date_value']) || !empty($cert['description'])): ?>
                  <div class="activity-meta">
                    <?php if (!empty($cert['description'])): ?>
                      <span class="activity-desc"><?php echo htmlspecialchars($cert['description']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($cert['date_value'])): ?>
                      <span class="activity-date"><?php echo htmlspecialchars($cert['date_value']); ?></span>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
              <?php if ($is_my_profile): ?>
                <form method="POST" action="delete_section.php" class="delete-form">
                  <input type="hidden" name="id" value="<?php echo $cert['id']; ?>">
                  <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                  <button type="submit" class="delete-item-btn">✕</button>
                </form>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- EDUCATION -->
      <div class="education">
        <h2>Education:</h2>
        <?php if (empty($educations)): ?>
          <p class="empty-section">— Aucune éducation ajoutée —</p>
        <?php else: ?>
          <?php foreach($educations as $edu): ?>
            <div class="activity-item">
              <div class="activity-dot"></div>
              <div class="activity-content">
                <div class="activity-title"><?php echo htmlspecialchars($edu['title'] ?? $edu['content'] ?? ''); ?></div>
                <?php if (!empty($edu['date_value']) || !empty($edu['description'])): ?>
                  <div class="activity-meta">
                    <?php if (!empty($edu['description'])): ?>
                      <span class="activity-desc"><?php echo htmlspecialchars($edu['description']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($edu['date_value'])): ?>
                      <span class="activity-date"><?php echo htmlspecialchars($edu['date_value']); ?></span>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
              <?php if ($is_my_profile): ?>
                <form method="POST" action="delete_section.php" class="delete-form">
                  <input type="hidden" name="id" value="<?php echo $edu['id']; ?>">
                  <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                  <button type="submit" class="delete-item-btn">✕</button>
                </form>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

</div>

<!-- Floating Post button with bubbles -->
<div class="post-bubble-wrapper">

  <button type="button" class="fab-post" id="post-trigger" aria-label="New post" title="New post">+</button>

  <div class="bubbles-container" id="bubbles-container">

    <!-- Bubble 1 — Immobilier -->
    <button type="button" class="bubble bubble-immo" data-modal="modal-immo" aria-label="Nouveau post Immobilier">
      <img src="../pages/icons/home.svg" alt="Immobilier">
      <span>Immobilier</span>
    </button>

    <!-- Bubble 2 — Events -->
    <button type="button" class="bubble bubble-events" data-modal="modal-events" aria-label="Nouveau post Events">
      <img src="../pages/icons/events.svg" alt="Events">
      <span>Events</span>
    </button>

    <!-- Bubble 3 — Stage -->
    <button type="button" class="bubble bubble-stage" data-modal="modal-stage" aria-label="Nouveau post Stage">
      <img src="../pages/icons/stage.svg" alt="Stage">
      <span>Stage</span>
    </button>

    <!-- Bubble 4 — Mentoring -->
    <button type="button" class="bubble bubble-mentoring" data-modal="modal-mentoring" aria-label="Nouveau post Mentoring">
      <img src="../pages/icons/mentoring.svg" alt="Mentoring">
      <span>Mentoring</span>
    </button>

    <!-- Bubble 5 — Bons Plans -->
    <button type="button" class="bubble bubble-bonplan" data-modal="modal-bonplan" aria-label="Nouveau post Bons Plans">
      <img src="../pages/icons/bonplan.svg" alt="Bons Plans">
      <span>Bons Plans</span>
    </button>

    <!-- Bubble 6 — Add new (empty, white outline) -->
    <button type="button" class="bubble bubble-add" data-modal="modal-add" aria-label="Nouveau post">
      <span>+</span>
    </button>

  </div>

</div>

<!-- ── POST MODALS ── -->

<!-- 1. IMMOBILIER -->
<div class="post-modal" id="modal-immo">
  <div class="modal-overlay" data-close></div>
  <div class="modal-card">
    <div class="modal-header immo">
      <h3>🏠 Nouveau post — Immobilier</h3>
      <button type="button" class="modal-close" data-close>&times;</button>
    </div>
    <div class="modal-body">
      <form action="create_post.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="category" value="immobilier">
        <!-- Titre -->
        <input type="text" name="title" placeholder="Titre de l'annonce..." class="modal-input" required>
        <!-- Description -->
        <textarea name="content" placeholder="Description du bien..." class="modal-textarea" required></textarea>
        <!-- Chambres -->
        <input type="number" name="chambres" placeholder="Nombre de chambres" class="modal-input" min="0" step="1">
        <!-- Prix -->
        <input type="number" name="price" placeholder="Prix en €" class="modal-input" min="0" step="1">
        <!-- Meublé / Non meublé -->
        <select name="meuble" class="modal-select" required>
          <option value="" disabled selected>Meublé ?</option>
          <option value="meublé">Meublé</option>
          <option value="non meublé">Non meublé</option>
        </select>
        <label class="modal-label">📷 Ajouter des photos <span style="color:#EF4444;">*</span></label>
        <input type="file" name="image" accept="image/*" class="modal-input" required>
        <button type="submit" class="modal-submit immo">📤 Publier l'annonce</button>
      </form>
    </div>
  </div>
</div>

<!-- 2. EVENTS -->
<div class="post-modal" id="modal-events">
  <div class="modal-overlay" data-close></div>
  <div class="modal-card">
    <div class="modal-header events">
      <h3>🎉 Nouveau post — Events</h3>
      <button type="button" class="modal-close" data-close>&times;</button>
    </div>
    <div class="modal-body">
      <form action="create_post.php" method="POST" enctype="multipart/form-data" id="form-events">
        <input type="hidden" name="category" value="events">
        <!-- Titre -->
        <input type="text" name="title" placeholder="Nom de l'événement..." class="modal-input" required>
        <!-- Description -->
        <textarea name="content" placeholder="Description de l'événement (programme, horaires...)" class="modal-textarea" required></textarea>
        <!-- Ville -->
        <input type="text" name="location" placeholder="Ville" class="modal-input" required>
        <!-- Type d'événement -->
        <input type="text" name="type_event" placeholder="Type d'événement (ex: Conférence, Concert, Soirée...)" class="modal-input">
        <!-- Date -->
        <input type="date" name="event_date" placeholder="Date" class="modal-input" required>
        <!-- Tarif + checkbox Gratuit -->
        <div class="modal-row modal-row-tarif">
          <input type="number" name="tarif" id="tarif-events" placeholder="Tarif en €" class="modal-input" min="0" step="0.01">
          <label class="modal-check-label">
            <input type="checkbox" name="is_free" value="gratuit" id="check-free" onchange="toggleTarif(this)">
            <span>Gratuit</span>
          </label>
        </div>
        <label class="modal-label">📷 Affiche ou photo de l'événement <span style="color:#EF4444;">*</span></label>
        <input type="file" name="image" accept="image/*" class="modal-input" required>
        <button type="submit" class="modal-submit events">📤 Publier l'événement</button>
      </form>
    </div>
  </div>
</div>

<!-- 3. STAGE -->
<div class="post-modal" id="modal-stage">
  <div class="modal-overlay" data-close></div>
  <div class="modal-card">
    <div class="modal-header stage">
      <h3>💼 Nouveau post — Stage</h3>
      <button type="button" class="modal-close" data-close>&times;</button>
    </div>
    <div class="modal-body">
      <form action="create_post.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="category" value="stage">
        <!-- Titre -->
        <input type="text" name="title" placeholder="Intitulé du poste (ex: Développeur Web...)" class="modal-input" required>
        <!-- Description -->
        <textarea name="content" placeholder="Description du stage (missions, profil recherché...)" class="modal-textarea" required></textarea>
        <!-- Domaine -->
        <input type="text" name="domaine" placeholder="Domaine (ex: Informatique, Marketing, Finance...)" class="modal-input">
        <!-- Ville -->
        <input type="text" name="location" placeholder="Ville" class="modal-input">
        <!-- Durée -->
        <input type="text" name="duree" placeholder="Durée (ex: 6 mois, 3 mois...)" class="modal-input">
        <!-- Type: Stage / Alternance -->
        <select name="type_stage" class="modal-select" required>
          <option value="" disabled selected>Type — Sélectionner...</option>
          <option value="stage">Stage</option>
          <option value="alternance">Alternance</option>
        </select>
        <!-- Niveau d'étude -->
        <input type="text" name="niveau_etude" placeholder="Niveau d'étude recherché (ex: Bac+3, Master...)" class="modal-input">
        <label class="modal-label">📷 Logo ou image de l'entreprise <span style="color:#EF4444;">*</span></label>
        <input type="file" name="image" accept="image/*" class="modal-input" required>
        <button type="submit" class="modal-submit stage">📤 Publier l'offre</button>
      </form>
    </div>
  </div>
</div>

<!-- 4. MENTORING -->
<div class="post-modal" id="modal-mentoring">
  <div class="modal-overlay" data-close></div>
  <div class="modal-card">
    <div class="modal-header mentoring">
      <h3>🎓 Nouveau post — Mentoring</h3>
      <button type="button" class="modal-close" data-close>&times;</button>
    </div>
    <div class="modal-body">
      <form action="create_post.php" method="POST">
        <input type="hidden" name="category" value="mentoring">
        <!-- Titre -->
        <input type="text" name="title" placeholder="Sujet de mentorat (ex: Aide en maths, CV review...)" class="modal-input" required>
        <!-- Description -->
        <textarea name="content" placeholder="Décrivez ce que vous proposez ou recherchez..." class="modal-textarea" required></textarea>
        <!-- Matière -->
        <input type="text" name="matiere" placeholder="Matière (ex: Mathématiques, Programmation...)" class="modal-input">
        <!-- Niveau -->
        <input type="text" name="niveau_mentoring" placeholder="Niveau (ex: Licence, Master, Débutant...)" class="modal-input">
        <!-- Langue -->
        <input type="text" name="langue" placeholder="Langue (ex: Français, Anglais...)" class="modal-input">
        <!-- Disponibilité -->
        <input type="date" name="disponibilite" placeholder="Disponibilité" class="modal-input">
        <!-- Je suis le mentor + Prix -->
        <div class="modal-row modal-row-tarif">
          <input type="number" name="prix_mentoring" id="prix-mentor" placeholder="Prix en €/heure" class="modal-input" min="0" step="0.01">
          <label class="modal-check-label">
            <input type="checkbox" name="is_mentor" value="mentor" id="check-mentor" onchange="toggleMentorPrice(this)">
            <span>Je suis le mentor</span>
          </label>
        </div>
        <button type="submit" class="modal-submit mentoring">📤 Publier</button>
      </form>
    </div>
  </div>
</div>

<!-- 5. BONS PLANS -->
<div class="post-modal" id="modal-bonplan">
  <div class="modal-overlay" data-close></div>
  <div class="modal-card">
    <div class="modal-header bonplan">
      <h3>💡 Nouveau post — Bons Plans</h3>
      <button type="button" class="modal-close" data-close>&times;</button>
    </div>
    <div class="modal-body">
      <form action="create_post.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="category" value="bonplan">
        <!-- Catégorie -->
        <select name="bonplan_category" class="modal-select" required>
          <option value="" disabled selected>Catégorie — Sélectionner...</option>
          <option value="livres">📚 Livres</option>
          <option value="elec">🔌 Électronique</option>
          <option value="meubles">🪑 Meubles</option>
          <option value="vetements">👕 Vêtements</option>
          <option value="cours">📖 Cours</option>
          <option value="divers">📦 Divers</option>
        </select>
        <!-- Produit -->
        <input type="text" name="produit" placeholder="Nom du produit..." class="modal-input" required>
        <!-- Description -->
        <textarea name="content" placeholder="Description du produit (détails, défauts, raison de vente...)" class="modal-textarea" required></textarea>
        <!-- État -->
        <input type="text" name="etat" placeholder="État (ex: Neuf, Bon état, Usagé...)" class="modal-input">
        <!-- Ville -->
        <input type="text" name="location" placeholder="Ville" class="modal-input">
        <!-- Prix -->
        <input type="number" name="price" placeholder="Prix en €" class="modal-input" min="0" step="0.01">
        <label class="modal-label">📷 Photo du produit <span style="color:#EF4444;">*</span></label>
        <input type="file" name="image" accept="image/*" class="modal-input" required>
        <button type="submit" class="modal-submit bonplan">📤 Publier le bon plan</button>
      </form>
    </div>
  </div>
</div>

<!-- 6. GENERAL -->
<div class="post-modal" id="modal-add">
  <div class="modal-overlay" data-close></div>
  <div class="modal-card">
    <div class="modal-header" style="background: linear-gradient(90deg, #1a3a40, #2E5961);">
      <h3>📝 Nouveau post général</h3>
      <button type="button" class="modal-close" data-close>&times;</button>
    </div>
    <div class="modal-body">
      <form action="create_post.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="category" value="general">
        <input type="text" name="title" placeholder="Titre de votre publication..." class="modal-input" required>
        <textarea name="content" placeholder="Qu'avez-vous à partager ?" class="modal-textarea" required></textarea>
        <label class="modal-label">📷 Image <span style="color:#EF4444;">*</span></label>
        <input type="file" name="image" accept="image/*" class="modal-input" required>
        <button type="submit" class="modal-submit" style="background: linear-gradient(90deg, #1a3a40, #2E5961);">📤 Publier</button>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
  /* ── POST BUBBLE ANIMATION ── */
  const postTrigger = document.getElementById('post-trigger');
  const bubblesContainer = document.getElementById('bubbles-container');
  const bubbles = bubblesContainer.querySelectorAll('.bubble');
  let bubblesOpen = false;

  postTrigger.addEventListener('click', function (e) {
    e.stopPropagation();
    bubblesOpen = !bubblesOpen;

    if (bubblesOpen) {
      postTrigger.classList.add('active');
      bubbles.forEach(function(bubble) {
        bubble.classList.add('visible');
      });
    } else {
      postTrigger.classList.remove('active');
      bubbles.forEach(function(bubble) {
        bubble.classList.remove('visible');
      });
    }
  });

  /* Close bubbles when clicking outside */
  document.addEventListener('click', function() {
    if (bubblesOpen) {
      bubblesOpen = false;
      postTrigger.classList.remove('active');
      bubbles.forEach(function(bubble) {
        bubble.classList.remove('visible');
      });
    }
  });

  /* ── MODAL LOGIC ── */
  const modals = document.querySelectorAll('.post-modal');

  bubbles.forEach(function(bubble) {
    bubble.addEventListener('click', function(e) {
      e.stopPropagation();
      const modalId = bubble.getAttribute('data-modal');
      if (!modalId) return;

      /* Close bubbles */
      bubblesOpen = false;
      postTrigger.classList.remove('active');
      bubbles.forEach(function(b) {
        b.classList.remove('visible');
      });

      /* Open modal */
      const modal = document.getElementById(modalId);
      if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
      }
    });
  });

  /* Close modal handlers */
  modals.forEach(function(modal) {
    const overlay = modal.querySelector('.modal-overlay');
    const closeBtn = modal.querySelector('.modal-close');

    function closeModal() {
      modal.classList.remove('active');
      document.body.style.overflow = '';
    }

    if (overlay) overlay.addEventListener('click', closeModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
  });

  /* Escape key closes any open modal */
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      modals.forEach(function(modal) {
        if (modal.classList.contains('active')) {
          modal.classList.remove('active');
          document.body.style.overflow = '';
        }
      });
    }
  });

  /* ── LEFT COLUMN EDIT TOGGLE ── */
  function toggleLeftEdit() {
    const view = document.getElementById('info-view');
    const edit = document.getElementById('info-edit');
    const btn  = document.getElementById('left-edit-btn');
    if (edit.style.display === 'none') {
      view.style.display = 'none';
      edit.style.display = 'block';
      btn.textContent    = '✕ Fermer';
    } else {
      view.style.display = 'block';
      edit.style.display = 'none';
      btn.textContent    = '✏️ Edit';
    }
  }

  /* ── BANNER EDIT TOGGLE ── */
  function toggleBannerEdit() {
    const view = document.getElementById('banner-view');
    const edit = document.getElementById('banner-edit');
    if (edit.style.display === 'none') {
      view.style.display = 'none';
      edit.style.display = 'block';
    } else {
      view.style.display = 'block';
      edit.style.display = 'none';
    }
  }

  /* ── RIGHT COLUMN EDIT TOGGLE ── */
  function toggleRightEdit() {
    const panel = document.getElementById('edit-panel');
    const btn   = document.getElementById('right-edit-btn');
    if (panel.style.display === 'none') {
      panel.style.display = 'block';
      btn.textContent     = '✕ Fermer';
    } else {
      panel.style.display = 'none';
      btn.textContent     = '✏️ Edit';
      document.getElementById('edit-input-wrap').style.display = 'none';
      document.getElementById('section-select').value = '';
    }
  }

  /* ── SHOW INPUT AFTER SELECTING SECTION ── */
  function showSectionInput(value) {
    const wrap = document.getElementById('edit-input-wrap');
    const type = document.getElementById('section-type');
    if (value) {
      wrap.style.display = 'block';
      type.value         = value;
    } else {
      wrap.style.display = 'none';
    }
  }

  /* ── EVENTS TARIF TOGGLE ── */
  function toggleTarif(checkbox) {
    const tarifInput = document.getElementById('tarif-events');
    if (checkbox.checked) {
      tarifInput.value = '';
      tarifInput.disabled = true;
      tarifInput.placeholder = 'Gratuit';
      tarifInput.style.opacity = '0.5';
    } else {
      tarifInput.disabled = false;
      tarifInput.placeholder = 'Tarif en €';
      tarifInput.style.opacity = '1';
    }
  }

  /* ── MENTOR PRICE TOGGLE ── */
  function toggleMentorPrice(checkbox) {
    const prixInput = document.getElementById('prix-mentor');
    if (checkbox.checked) {
      prixInput.disabled = false;
      prixInput.placeholder = 'Prix en €/heure';
      prixInput.style.opacity = '1';
    } else {
      prixInput.value = '';
      prixInput.disabled = true;
      prixInput.placeholder = 'Annonce sans prix';
      prixInput.style.opacity = '0.5';
    }
  }

  // Init mentor price input as disabled by default
  (function initMentorPrice() {
    const prixInput = document.getElementById('prix-mentor');
    if (prixInput) {
      prixInput.disabled = true;
      prixInput.placeholder = 'Annonce sans prix';
      prixInput.style.opacity = '0.5';
    }
  })();

  /* expose to window for inline handlers */
  window.toggleLeftEdit   = toggleLeftEdit;
  window.toggleBannerEdit = toggleBannerEdit;
  window.toggleRightEdit  = toggleRightEdit;
  window.showSectionInput = showSectionInput;
  window.toggleTarif      = toggleTarif;
  window.toggleMentorPrice = toggleMentorPrice;

  /* ── Profile Chat Tab ── */
  let profileChatOpen = false;
  let profileChatPoll = null;
  let profileChatConvId = null;
  let profileChatLastId = 0;
  const profileUserId = <?php echo $profile_id; ?>;
  const myUserId = <?php echo $my_id; ?>;

  window.toggleProfileChat = async function() {
    const tab = document.getElementById('profile-chat-tab');
    profileChatOpen = !profileChatOpen;
    if (profileChatOpen) {
      tab.style.opacity = '1';
      tab.style.transform = 'scale(1)';
      tab.style.pointerEvents = 'all';
      await loadProfileChat();
      if (profileChatPoll) clearInterval(profileChatPoll);
      profileChatPoll = setInterval(loadProfileChat, 3000);
    } else {
      tab.style.opacity = '0';
      tab.style.transform = 'scale(0.95)';
      tab.style.pointerEvents = 'none';
      if (profileChatPoll) clearInterval(profileChatPoll);
    }
  };

  async function loadProfileChat() {
    try {
      // Try to find conversation first
      const convRes = await fetch('../pages/api/get_conversations.php');
      const convData = await convRes.json();
      if (!convData.error && convData.conversations) {
        const conv = convData.conversations.find(c => c.other_user.id === profileUserId);
        if (conv) {
          profileChatConvId = conv.conversation_id;
          const msgRes = await fetch('../pages/api/get_messages.php?conversation_id=' + conv.conversation_id + (profileChatLastId > 0 ? '&after=' + profileChatLastId : ''));
          const msgData = await msgRes.json();
          if (!msgData.error) renderProfileChatMessages(msgData.messages);
          return;
        }
      }
      // No conversation yet
      document.getElementById('profile-chat-body').innerHTML = '<p class="chat-placeholder">Commencez la conversation...</p>';
    } catch (e) { console.error(e); }
  }

  function renderProfileChatMessages(messages) {
    const body = document.getElementById('profile-chat-body');
    if (!messages || messages.length === 0) {
      body.innerHTML = '<p class="chat-placeholder">Commencez la conversation...</p>';
      return;
    }
    let html = '';
    messages.forEach(msg => {
      if (msg.id > profileChatLastId) profileChatLastId = msg.id;
      const isMe = msg.sender_id === myUserId;
      const bubbleStyle = isMe
        ? 'background:linear-gradient(135deg,#2E5961,#3e7b86);color:#fff;border-radius:12px 12px 2px 12px;align-self:flex-end;'
        : 'background:#f0f2f5;color:#374151;border-radius:12px 12px 12px 2px;align-self:flex-start;';
      html += '<div style="display:flex;flex-direction:column;gap:2px;margin-bottom:8px;' + (isMe ? 'align-items:flex-end;' : 'align-items:flex-start;') + '">' +
        '<div style="' + bubbleStyle + 'padding:8px 12px;font-size:0.88rem;max-width:85%;word-break:break-word;">' + escapeHtml(msg.content) + '</div>' +
        '<span style="font-size:0.7rem;color:#9ca3af;">' + timeAgoShort(msg.created_at) + (isMe ? (msg.is_read ? ' ✓✓' : ' ✓') : '') + '</span>' +
      '</div>';
    });
    body.innerHTML = html;
    body.scrollTop = body.scrollHeight;
  }

  window.sendProfileChatMessage = async function() {
    const input = document.getElementById('profile-chat-input');
    const content = input.value.trim();
    if (!content) return;
    input.value = '';

    const formData = new FormData();
    if (profileChatConvId) formData.append('conversation_id', profileChatConvId);
    formData.append('receiver_id', profileUserId);
    formData.append('content', content);

    try {
      const res = await fetch('../pages/api/send_message.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.error || !data.success) return;
      if (!profileChatConvId && data.conversation_id) {
        profileChatConvId = data.conversation_id;
      }
      await loadProfileChat();
    } catch (e) { console.error(e); }
  };

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function timeAgoShort(datetime) {
    const time = new Date(datetime).getTime();
    const now = Date.now();
    const diff = Math.floor((now - time) / 1000);
    if (diff < 60) return 'À l\'instant';
    if (diff < 3600) return Math.floor(diff / 60) + ' min';
    if (diff < 86400) return Math.floor(diff / 3600) + ' h';
    return Math.floor(diff / 86400) + ' j';
  }

})();
</script>
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

<?php include '../includes/brainpool_widget.php'; ?>
</body>
</html>