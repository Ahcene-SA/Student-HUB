<?php
session_start();

$conn = new mysqli("127.0.0.1", "root", "", "devweb", 3306);

if (!isset($_SESSION['id_user'])) {
    header("Location: ../persoinfo/signin.php");
    exit();
}

$user_id = $_SESSION['id_user'];

// Get current user
$stmt = $conn->prepare("SELECT * FROM user WHERE id_user = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Auto-create posts table
$conn->query("
    CREATE TABLE IF NOT EXISTS posts (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        user_id     INT NOT NULL,
        category    VARCHAR(30) NOT NULL DEFAULT 'general',
        title       VARCHAR(255),
        content     TEXT NOT NULL,
        image       VARCHAR(255),
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_category (category),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Auto-create likes and comments tables
$conn->query("
    CREATE TABLE IF NOT EXISTS post_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_post_like (post_id, user_id),
        INDEX idx_post (post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$conn->query("
    CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_post (post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$conn->query("
    CREATE TABLE IF NOT EXISTS comment_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        comment_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_comment_like (comment_id, user_id),
        INDEX idx_comment (comment_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Fetch general posts with author info, like counts and comment counts
$posts_stmt = $conn->prepare("
    SELECT
        p.*,
        u.prenom, u.nom, u.username, u.avatar,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) AS like_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count,
        EXISTS(SELECT 1 FROM post_likes WHERE post_id = p.id AND user_id = ?) AS user_liked
    FROM posts p
    JOIN user u ON u.id_user = p.user_id
    WHERE p.category = 'general'
    ORDER BY p.created_at DESC
");
$posts_stmt->bind_param("i", $user_id);
$posts_stmt->execute();
$posts_result = $posts_stmt->get_result();

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    if ($diff < 60) return 'À l\'instant';
    if ($diff < 3600) return 'Il y a ' . floor($diff / 60) . ' min';
    if ($diff < 86400) return 'Il y a ' . floor($diff / 3600) . ' h';
    if ($diff < 604800) return 'Il y a ' . floor($diff / 86400) . ' j';
    return date('d M Y', $time);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student HUB - Home</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="./home.css?v=2" />
  <style>
    /* ── Post detail modal ── */
    .post-detail-modal {
      position: fixed; inset: 0; z-index: 3000;
      display: flex; align-items: center; justify-content: center;
      opacity: 0; pointer-events: none;
      transition: opacity 0.25s ease;
    }
    .post-detail-modal.active { opacity: 1; pointer-events: all; }
    .detail-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.5); }
    .detail-card {
      position: relative; background: #fff; border-radius: 16px;
      width: 100%; max-width: 640px; max-height: 85vh; overflow-y: auto;
      margin: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.25);
      transform: scale(0.92) translateY(20px);
      transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .post-detail-modal.active .detail-card { transform: scale(1) translateY(0); }
    .detail-close {
      position: absolute; top: 12px; right: 16px;
      background: none; border: none; font-size: 1.8rem; cursor: pointer; color: #666; z-index: 10;
    }
    .detail-image {
      width: 100%; max-height: 320px; object-fit: cover; border-radius: 12px; margin-bottom: 16px;
    }
    .detail-field { margin-bottom: 14px; }
    .detail-field-label {
      font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.06em;
      color: #888; margin-bottom: 4px; font-weight: 700;
    }
    .detail-field-value { font-size: 0.95rem; color: #1a2332; }
    .detail-content { line-height: 1.65; color: #333; white-space: pre-wrap; }
    .post_card { cursor: pointer; transition: box-shadow 0.2s ease; }
    .post_card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }

    /* ── Comment modal (critical inline styles) ── */
    .comment-modal {
      position: fixed; inset: 0; z-index: 4000;
      display: flex; align-items: center; justify-content: center;
      opacity: 0; pointer-events: none;
      transition: opacity 0.25s ease;
    }
    .comment-modal.active { opacity: 1; pointer-events: all; }
    .comment-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.5); }
    .comment-card {
      position: relative; background: #fff; border-radius: 16px;
      width: 100%; max-width: 560px; max-height: 85vh; overflow: hidden;
      margin: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.25);
      display: flex; flex-direction: column;
      transform: scale(0.92) translateY(20px);
      transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .comment-modal.active .comment-card { transform: scale(1) translateY(0); }
    .comment-close {
      position: absolute; top: 10px; right: 14px;
      background: none; border: none; font-size: 1.8rem; cursor: pointer; color: #666; z-index: 10;
      width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 50%;
    }
    .comment-close:hover { background: rgba(0,0,0,0.06); }
    .comment-post-preview { padding: 20px 24px 12px; border-bottom: 1px solid #E2E6EA; }
    .comment-preview-head { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 0.95rem; color: #1a2332; margin-bottom: 8px; }
    .comment-preview-head .avatar, .comment-preview-head img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
    .comment-preview-body { font-size: 0.92rem; color: #374151; line-height: 1.5; }
    .comment-preview-img { width: 100%; max-height: 200px; object-fit: cover; border-radius: 12px; margin-top: 10px; display: block; }
    .comment-post-likebar { padding: 10px 24px; border-bottom: 1px solid #E2E6EA; display: flex; align-items: center; }
    .comments-list { flex: 1; overflow-y: auto; padding: 12px 24px; min-height: 120px; max-height: 45vh; }
    .comments-empty { text-align: center; color: #9ca3af; font-size: 0.9rem; padding: 24px 0; font-style: italic; }
    .comment-item { display: flex; gap: 10px; padding: 10px 0; border-bottom: 1px solid #F0F2F5; }
    .comment-item:last-child { border-bottom: none; }
    .comment-avatar { width: 36px; height: 36px; border-radius: 50%; background: #2E5961; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; overflow: hidden; }
    .comment-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .comment-body { flex: 1; display: flex; flex-direction: column; gap: 4px; }
    .comment-meta { display: flex; align-items: center; gap: 8px; }
    .comment-author { font-weight: 700; font-size: 0.9rem; color: #1a2332; }
    .comment-time { font-size: 0.78rem; color: #9ca3af; }
    .comment-text { font-size: 0.9rem; color: #374151; line-height: 1.5; }
    .comment-like-btn { display: inline-flex; align-items: center; gap: 4px; background: none; border: none; font-size: 0.8rem; color: #9ca3af; cursor: pointer; padding: 2px 6px; border-radius: 6px; }
    .comment-like-btn:hover { background: rgba(46,89,97,0.06); color: #2E5961; }
    .comment-like-btn.liked { color: #EF4444; }
    .comment-form-wrap { padding: 12px 24px 20px; border-top: 1px solid #E2E6EA; background: #F8FAFB; }
    #comment-form { display: flex; flex-direction: column; gap: 10px; }
    #comment-content { width: 100%; min-height: 60px; max-height: 120px; border: 1.5px solid #D1D5DB; border-radius: 12px; padding: 10px 14px; font-family: 'ROBOTO', sans-serif; font-size: 0.9rem; color: #374151; outline: none; resize: vertical; }
    #comment-content:focus { border-color: #2E5961; box-shadow: 0 0 0 3px rgba(46,89,97,0.10); }
    .comment-submit-btn { align-self: flex-end; background: linear-gradient(90deg, #2E5961, #3e7b86); color: #fff; border: none; border-radius: 999px; padding: 8px 20px; font-family: 'ROBOTO', sans-serif; font-weight: 700; font-size: 0.9rem; cursor: pointer; }
  </style>
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
        <a href="home.php" class="active">Home</a>
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
            <a href="#" class="user-menu-item"><span class="user-menu-icon">⚙️</span> Settings</a>
            <a href="#" class="user-menu-item"><span class="user-menu-icon">❓</span> Help</a>
            <div class="user-menu-divider"></div>
            <a href="#" class="user-menu-item user-menu-logout"><span class="user-menu-icon">🚪</span> Log out</a>
          </div>
        </div>
      </nav>
    </header>

    <!-- HOME LAYOUT -->
    <section class="home_layout">

      <!-- LEFT SIDEBAR -->
      <aside class="left_sidebar">
        <div class="card profile_card">
          <div class="profile_top">
            <div class="avatar"><?php echo !empty($user['avatar']) ? '<img src="../profile/' . htmlspecialchars($user['avatar']) . '" alt="avatar" />' : '👤'; ?></div>
            <div class="profile_meta">
              <h3><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h3>
              <p>@<?php echo htmlspecialchars($user['username']); ?></p>
            </div>
          </div>
          <a class="voir-btn" href="../profile/profile.php?id=<?php echo $user_id; ?>">Voir le profil</a>
        </div>
        <div class="card nav_card">
          <a href="#">Live</a>
          <a href="#">Contacts</a>
          <a href="#">Video</a>
          <a href="#">Saved</a>
        </div>
      </aside>

      <!-- MAIN FEED -->
      <main class="feed_area">

        <!-- COMPOSER -->
        <div class="card composer_card">
          <form action="../profile/create_post.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="category" value="general" />
            <h2>Create a post</h2>
            <p class="muted">Share something with the community.</p>
            <input type="text" name="title" placeholder="Titre..." class="modal-input" style="width:100%;margin-bottom:10px;border:1.5px solid #D1D5DB;border-radius:12px;padding:10px 14px;font-family:Inter;" />
            <textarea name="content" placeholder="Share something with the community..." required style="width:100%;min-height:80px;border:1.5px solid #D1D5DB;border-radius:12px;padding:10px 14px;font-family:Inter;resize:vertical;"></textarea>
            <div class="composer_actions" style="margin-top:10px;display:flex;justify-content:space-between;align-items:center;">
              <input type="file" name="image" accept="image/*" class="file-upload-input" />
              <button class="primary_btn" type="submit">Publish</button>
            </div>
          </form>
        </div>

        <!-- REAL POSTS -->
        <?php if ($posts_result->num_rows === 0): ?>
          <div class="card" style="padding:2rem;text-align:center;color:#666;">
            <p>Aucune publication pour le moment.</p>
            <p class="muted" style="font-size:0.9rem;">Soyez le premier à publier quelque chose !</p>
          </div>
        <?php else: ?>
          <?php while ($post = $posts_result->fetch_assoc()): ?>
            <?php
            $detailHtml = '';
            if (!empty($post['image'])) {
                $detailHtml .= '<img src="../profile/' . htmlspecialchars($post['image']) . '" alt="" class="detail-image">';
            }
            $detailHtml .= '<h2 style="margin:0 0 8px;font-size:1.3rem;font-weight:700;color:#1a2332;">' . htmlspecialchars($post['title'] ?: 'Publication') . '</h2>';
            $detailHtml .= '<div style="font-size:0.85rem;color:#888;margin-bottom:16px;">' . htmlspecialchars($post['prenom'] . ' ' . $post['nom']) . ' · ' . timeAgo($post['created_at']) . '</div>';
            $detailHtml .= '<div class="detail-content">' . nl2br(htmlspecialchars($post['content'])) . '</div>';
            ?>
            <article class="card post_card" onclick="openPostDetail(this)" data-post-id="<?php echo $post['id']; ?>" data-detail-html="<?php echo base64_encode($detailHtml); ?>">
              <div class="post_head">
                <div class="post_user">
                  <div class="avatar sm"><?php echo !empty($post['avatar']) ? '<img src="../profile/' . htmlspecialchars($post['avatar']) . '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">' : '👤'; ?></div>
                  <div class="post_user_meta">
                    <h4><?php echo htmlspecialchars($post['prenom'] . ' ' . $post['nom']); ?></h4>
                    <p><?php echo timeAgo($post['created_at']); ?></p>
                  </div>
                </div>
                <?php if ($post['user_id'] != $user_id): ?>
                  <a href="../profile/profile.php?id=<?php echo $post['user_id']; ?>" class="follow_btn" style="text-decoration:none;" onclick="event.stopPropagation();">Voir profil</a>
                <?php endif; ?>
              </div>
              <?php if (!empty($post['title'])): ?>
                <h4 style="margin:0 0 8px;font-size:1.05rem;font-weight:700;color:#1a2332;"><?php echo htmlspecialchars($post['title']); ?></h4>
              <?php endif; ?>
              <p class="post_desc"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
              <?php if (!empty($post['image'])): ?>
                <div class="post_media">
                  <img src="../profile/<?php echo htmlspecialchars($post['image']); ?>" alt="Post image" />
                </div>
              <?php endif; ?>
              <div class="post_actions" onclick="event.stopPropagation();">
                <div class="post_actions_left">
                  <button type="button" class="action-btn like-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>" onclick="toggleLike(this, <?php echo $post['id']; ?>)">
                    <span class="like-icon"><?php echo $post['user_liked'] ? '❤️' : '🤍'; ?></span>
                    <span class="like-count"><?php echo $post['like_count']; ?></span>
                  </button>
                  <button type="button" class="action-btn comment-btn" onclick="openCommentModal(<?php echo $post['id']; ?>)">
                    💬 <span class="comment-count"><?php echo $post['comment_count']; ?></span>
                  </button>
                  <button type="button" class="action-btn share-btn">🔗 Share</button>
                </div>
                <?php if ($post['user_id'] == $user_id): ?>
                  <form method="POST" action="../profile/delete_post.php" style="display:inline;" onsubmit="return confirm('Delete this post?');">
                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>" />
                    <button type="submit" class="delete-post-btn">🗑️ Delete</button>
                  </form>
                <?php endif; ?>
              </div>
            </article>
          <?php endwhile; ?>
        <?php endif; ?>

      </main>

      <!-- RIGHT SIDEBAR -->
      <aside class="right_sidebar">
        <div class="card ads_card">
          <h3>Publicités</h3>
          <div class="ad_box">Espace Pub 1</div>
          <div class="ad_box">Espace Pub 2</div>
        </div>
        <div class="card tips_card">
          <h3>Conseils rapides</h3>
          <ul>
            <li>Vérifie les profils avant contact</li>
            <li>Partage uniquement du contenu utile</li>
            <li>Reste respectueux avec la communauté</li>
          </ul>
        </div>
      </aside>

    </section>
  </div>

  <?php $widget_base_path = '../'; include '../includes/chat_widget.php'; ?>

  <!-- Post Detail Modal -->
  <div class="post-detail-modal" id="post-detail-modal">
    <div class="detail-overlay" onclick="closePostDetail()"></div>
    <div class="detail-card">
      <button class="detail-close" onclick="closePostDetail()">×</button>
      <div id="detail-body-content" style="padding:24px;"></div>
    </div>
  </div>

  <!-- Comment Overlay Modal -->
  <div class="comment-modal" id="comment-modal">
    <div class="comment-overlay" onclick="closeCommentModal()"></div>
    <div class="comment-card">
      <button class="comment-close" onclick="closeCommentModal()">×</button>

      <!-- Post preview -->
      <div class="comment-post-preview" id="comment-post-preview"></div>

      <!-- Post like bar -->
      <div class="comment-post-likebar">
        <button type="button" class="action-btn like-btn" id="modal-like-btn" onclick="toggleModalLike()">
          <span class="like-icon">🤍</span>
          <span class="like-count" id="modal-like-count">0</span>
        </button>
      </div>

      <!-- Comments list -->
      <div class="comments-list" id="comments-list">
        <p class="comments-empty">Aucun commentaire pour le moment.</p>
      </div>

      <!-- Add comment form -->
      <div class="comment-form-wrap">
        <form id="comment-form" onsubmit="submitComment(event)">
          <input type="hidden" id="comment-post-id" value="" />
          <textarea id="comment-content" placeholder="Écrire un commentaire..." required></textarea>
          <button type="submit" class="comment-submit-btn">Publier</button>
        </form>
      </div>
    </div>
  </div>

  <script>
    let currentPostId = null;
    let currentPostLiked = false;

    /* ── Post Detail (existing) ── */
    function openPostDetail(el) {
      const modal = document.getElementById('post-detail-modal');
      const body  = document.getElementById('detail-body-content');
      body.innerHTML = decodeURIComponent(escape(atob(el.getAttribute('data-detail-html'))));
      modal.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    function closePostDetail() {
      document.getElementById('post-detail-modal').classList.remove('active');
      document.body.style.overflow = '';
    }

    /* ── Like toggle on feed ── */
    async function toggleLike(btn, postId) {
      const formData = new FormData();
      formData.append('post_id', postId);
      try {
        const res = await fetch('api/like_post.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.error) return;
        const icon = btn.querySelector('.like-icon');
        const count = btn.querySelector('.like-count');
        icon.textContent = data.liked ? '❤️' : '🤍';
        count.textContent = data.count;
        btn.classList.toggle('liked', data.liked);
      } catch (e) { console.error(e); }
    }

    /* ── Comment Modal ── */
    async function openCommentModal(postId) {
      console.log('openCommentModal called with postId:', postId);
      currentPostId = postId;
      document.getElementById('comment-post-id').value = postId;
      const modal = document.getElementById('comment-modal');
      console.log('modal element:', modal);
      modal.classList.add('active');
      console.log('active class added');
      document.body.style.overflow = 'hidden';

      // Find post data from DOM
      const article = document.querySelector('article[data-post-id="' + postId + '"]');
      console.log('article found:', article);
      const preview = document.getElementById('comment-post-preview');
      if (article) {
        const author = article.querySelector('.post_user_meta h4')?.textContent || '';
        const avatar = article.querySelector('.post_user .avatar.sm')?.innerHTML || '👤';
        const content = article.querySelector('.post_desc')?.innerHTML || '';
        const image = article.querySelector('.post_media img');
        let html = '<div class="comment-preview-head">' + avatar + '<span>' + author + '</span></div>';
        html += '<div class="comment-preview-body">' + content + '</div>';
        if (image) html += '<img src="' + image.src + '" class="comment-preview-img" />';
        preview.innerHTML = html;
      }

      // Load comments
      await loadComments(postId);
    }

    function closeCommentModal() {
      document.getElementById('comment-modal').classList.remove('active');
      document.body.style.overflow = '';
      currentPostId = null;
    }

    async function loadComments(postId) {
      try {
        const res = await fetch('api/get_comments.php?post_id=' + postId);
        const data = await res.json();
        if (data.error) return;

        // Update modal like state
        currentPostLiked = data.post_user_liked;
        const modalLikeBtn = document.getElementById('modal-like-btn');
        const modalLikeIcon = modalLikeBtn.querySelector('.like-icon');
        const modalLikeCount = document.getElementById('modal-like-count');
        modalLikeIcon.textContent = data.post_user_liked ? '❤️' : '🤍';
        modalLikeCount.textContent = data.post_like_count;
        modalLikeBtn.classList.toggle('liked', data.post_user_liked);

        // Update feed button too
        const feedBtn = document.querySelector('article[data-post-id="' + postId + '"] .like-btn');
        if (feedBtn) {
          feedBtn.querySelector('.like-icon').textContent = data.post_user_liked ? '❤️' : '🤍';
          feedBtn.querySelector('.like-count').textContent = data.post_like_count;
          feedBtn.classList.toggle('liked', data.post_user_liked);
        }

        // Render comments
        const list = document.getElementById('comments-list');
        if (!data.comments || data.comments.length === 0) {
          list.innerHTML = '<p class="comments-empty">Aucun commentaire pour le moment.</p>';
          return;
        }
        list.innerHTML = data.comments.map(c => `
          <div class="comment-item" data-comment-id="${c.id}">
            <div class="comment-avatar">${c.avatar ? '<img src="../profile/' + c.avatar + '" />' : '👤'}</div>
            <div class="comment-body">
              <div class="comment-meta">
                <span class="comment-author">${escapeHtml(c.author)}</span>
                <span class="comment-time">${timeAgoJs(c.created_at)}</span>
              </div>
              <div class="comment-text">${escapeHtml(c.content)}</div>
              <button type="button" class="comment-like-btn ${c.user_liked ? 'liked' : ''}" onclick="toggleCommentLike(this, ${c.id})">
                <span>${c.user_liked ? '❤️' : '🤍'}</span> <span class="comment-like-count">${c.like_count}</span>
              </button>
            </div>
          </div>
        `).join('');
      } catch (e) { console.error(e); }
    }

    async function toggleModalLike() {
      if (!currentPostId) return;
      await toggleLike(document.querySelector('article[data-post-id="' + currentPostId + '"] .like-btn'), currentPostId);
      // Refresh modal state
      const feedBtn = document.querySelector('article[data-post-id="' + currentPostId + '"] .like-btn');
      if (feedBtn) {
        const liked = feedBtn.classList.contains('liked');
        const count = feedBtn.querySelector('.like-count').textContent;
        document.getElementById('modal-like-btn').querySelector('.like-icon').textContent = liked ? '❤️' : '🤍';
        document.getElementById('modal-like-count').textContent = count;
        document.getElementById('modal-like-btn').classList.toggle('liked', liked);
      }
    }

    async function toggleCommentLike(btn, commentId) {
      const formData = new FormData();
      formData.append('comment_id', commentId);
      try {
        const res = await fetch('api/like_comment.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.error) return;
        btn.querySelector('span').textContent = data.liked ? '❤️' : '🤍';
        btn.querySelector('.comment-like-count').textContent = data.count;
        btn.classList.toggle('liked', data.liked);
      } catch (e) { console.error(e); }
    }

    async function submitComment(e) {
      e.preventDefault();
      const postId = document.getElementById('comment-post-id').value;
      const content = document.getElementById('comment-content').value.trim();
      if (!content) return;
      const formData = new FormData();
      formData.append('post_id', postId);
      formData.append('content', content);
      try {
        const res = await fetch('api/add_comment.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.error || !data.success) return;
        document.getElementById('comment-content').value = '';
        await loadComments(postId);
        // Update comment count on feed button
        const feedCommentBtn = document.querySelector('article[data-post-id="' + postId + '"] .comment-btn .comment-count');
        if (feedCommentBtn) {
          feedCommentBtn.textContent = parseInt(feedCommentBtn.textContent) + 1;
        }
      } catch (e) { console.error(e); }
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function timeAgoJs(datetime) {
      const time = new Date(datetime).getTime();
      const now = Date.now();
      const diff = Math.floor((now - time) / 1000);
      if (diff < 60) return 'À l\'instant';
      if (diff < 3600) return 'Il y a ' + Math.floor(diff / 60) + ' min';
      if (diff < 86400) return 'Il y a ' + Math.floor(diff / 3600) + ' h';
      if (diff < 604800) return 'Il y a ' + Math.floor(diff / 86400) + ' j';
      return new Date(datetime).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closePostDetail();
        closeCommentModal();
      }
    });
  </script>
</body>
</html>
