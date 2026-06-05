<?php
session_start();
$user_id = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;

$conn = new mysqli("127.0.0.1", "root", "", "devweb", 3306);
$conn->query("
    CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, category VARCHAR(30) DEFAULT 'general',
        title VARCHAR(255), content TEXT NOT NULL, image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user(user_id), INDEX idx_category(category), INDEX idx_created(created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
$where = ["p.category = 'bonplan'"];
$params = [];
$types = "";

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $where[] = "(p.title LIKE ? OR p.content LIKE ? OR p.produit LIKE ? OR p.location LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "ssss";
}

$cat = isset($_GET['cat']) ? trim($_GET['cat']) : '';
if ($cat !== '') {
    $where[] = "p.bonplan_category = ?";
    $params[] = $cat;
    $types .= "s";
}

$prix = isset($_GET['prix']) ? trim($_GET['prix']) : '';
if ($prix !== '') {
    $where[] = "CAST(p.price AS UNSIGNED) <= ?";
    $params[] = $prix;
    $types .= "s";
}

$etat = isset($_GET['etat']) ? trim($_GET['etat']) : '';
if ($etat !== '') {
    if ($etat === 'neuf') {
        $where[] = "p.etat LIKE ?";
        $params[] = '%neuf%';
        $types .= "s";
    } elseif ($etat === 'bon') {
        $where[] = "p.etat LIKE ?";
        $params[] = '%bon%';
        $types .= "s";
    } elseif ($etat === 'usage') {
        $where[] = "p.etat LIKE ?";
        $params[] = '%usag%';
        $types .= "s";
    }
}

$ville = isset($_GET['ville']) ? trim($_GET['ville']) : '';
if ($ville !== '') {
    $where[] = "p.location LIKE ?";
    $params[] = '%' . $ville . '%';
    $types .= "s";
}

$sql = "SELECT p.*, u.prenom, u.nom, u.username, u.avatar
    FROM posts p JOIN user u ON u.id_user = p.user_id
    WHERE " . implode(" AND ", $where) . " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$posts = $stmt->get_result();

function timeAgo($d) {
    $diff = time() - strtotime($d);
    if ($diff < 60) return "À l'instant";
    if ($diff < 3600) return floor($diff/60).' min';
    if ($diff < 86400) return floor($diff/3600).' h';
    if ($diff < 604800) return floor($diff/86400).' j';
    return date('d M Y', strtotime($d));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student HUB – Bons Plans</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700&family=Syne:wght@700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="bonplan.css" />
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
    /* Card click */
    .bp-card { cursor: pointer; transition: transform 0.15s ease, box-shadow 0.2s ease; }
    .bp-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.12); }
    /* Text-only cards (no image) */
    .bp-card.no-image .bp-card-img-wrap { display: none; }
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
        <a href="home.php">Home</a>
        <a href="immobilier.php">Immobilier</a>
        <a href="stage.php">Stage</a>
        <a href="events.php">Events</a>
        <a href="mentoring.php">Mentoring</a>
        <a href="bonplan.php" class="active">Bons plans</a>
        <div class="user-menu">
          <button class="post_btn user-menu-trigger" tabindex="0">
            Mon compte ▾
          </button>
          <div class="user-menu-dropdown">
            <a href="../profile/profile.php" class="user-menu-item">
              <span class="user-menu-icon">👤</span> Profile
            </a>
            <a href="#" class="user-menu-item">
              <span class="user-menu-icon">⚙️</span> Settings
            </a>
            <a href="#" class="user-menu-item">
              <span class="user-menu-icon">❓</span> Help
            </a>
            <div class="user-menu-divider"></div>
            <a href="#" class="user-menu-item user-menu-logout">
              <span class="user-menu-icon">🚪</span> Log out
            </a>
          </div>
        </div>
      </nav>
    </header>

    <!-- PAGE BONS PLANS -->
    <main class="bp-page">
      <section class="bp-shell">

        <!-- HERO -->
        <div class="bp-hero-card reveal" data-hero-slider>
          <img src="../img/bp/download (1).png" alt="Bons plans étudiants" class="bp-hero-slide active" />
          <img src="../img/bp/download.png" alt="Marketplace étudiant" class="bp-hero-slide" />
          <img src="../img/bp/ef0100fbad184d741f743a9b63ee1816.jpg" alt="Annonces étudiantes" class="bp-hero-slide" />

          <div class="bp-hero-gradient"></div>
          <div class="bp-hero-text">
            <h1>Bons Plans</h1>
            <p>Achète, vends, échange entre étudiants — simplement.</p>
          </div>

          <div class="bp-hero-dots"></div>
        </div>

        <form method="GET" action="bonplan.php">
        <!-- TOP BAR -->
        <div class="bp-top-bar reveal">
          <div class="bp-search-row">
            <input type="text" name="search" class="bp-search-input" placeholder="Titre, catégorie, ville..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" />
            <button type="submit" class="bp-search-btn">Rechercher</button>
          </div>
        </div>

        <!-- FILTERS -->
        <div class="bp-filters-board reveal">
          <div class="bp-filter-item">
            <label for="f-cat">Catégorie</label>
            <select id="f-cat" name="cat" class="bp-select">
              <option value="">Choisir</option>
              <option value="livres" <?php if (isset($_GET['cat']) && $_GET['cat'] === 'livres') echo 'selected'; ?>>Livres</option>
              <option value="elec" <?php if (isset($_GET['cat']) && $_GET['cat'] === 'elec') echo 'selected'; ?>>Électronique</option>
              <option value="meubles" <?php if (isset($_GET['cat']) && $_GET['cat'] === 'meubles') echo 'selected'; ?>>Meubles</option>
              <option value="vetements" <?php if (isset($_GET['cat']) && $_GET['cat'] === 'vetements') echo 'selected'; ?>>Vêtements</option>
              <option value="cours" <?php if (isset($_GET['cat']) && $_GET['cat'] === 'cours') echo 'selected'; ?>>Cours</option>
              <option value="divers" <?php if (isset($_GET['cat']) && $_GET['cat'] === 'divers') echo 'selected'; ?>>Divers</option>
            </select>
          </div>

          <div class="bp-filter-item">
            <label for="f-prix">Prix max (€)</label>
            <input type="number" id="f-prix" name="prix" class="bp-select" placeholder="Ex: 50" min="0" step="1" value="<?php echo htmlspecialchars(isset($_GET['prix']) ? $_GET['prix'] : ''); ?>" />
          </div>

          <div class="bp-filter-item">
            <label for="f-etat">État</label>
            <select id="f-etat" name="etat" class="bp-select">
              <option value="">Choisir</option>
              <option value="neuf" <?php if (isset($_GET['etat']) && $_GET['etat'] === 'neuf') echo 'selected'; ?>>Neuf</option>
              <option value="bon" <?php if (isset($_GET['etat']) && $_GET['etat'] === 'bon') echo 'selected'; ?>>Bon état</option>
              <option value="usage" <?php if (isset($_GET['etat']) && $_GET['etat'] === 'usage') echo 'selected'; ?>>Usagé</option>
            </select>
          </div>

          <div class="bp-filter-item">
            <label for="f-ville">Ville</label>
            <input type="text" id="f-ville" name="ville" class="bp-select" placeholder="Ex: Paris, Lyon..." value="<?php echo htmlspecialchars(isset($_GET['ville']) ? $_GET['ville'] : ''); ?>" />
          </div>

          <div class="bp-filter-actions">
            <button type="submit" class="bp-filter-btn apply">Appliquer</button>
            <a href="bonplan.php" class="bp-filter-btn reset" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">Reset</a>
          </div>
        </div>
        </form>

        <!-- LIST -->
        <section class="bp-list">
          <header class="bp-list-header reveal">
            <h2>Annonces récentes</h2>
            <p><?php echo $posts->num_rows; ?> annonce(s)</p>
          </header>

          <div class="bp-cards">
            <!-- REAL POSTS -->
            <?php if ($posts->num_rows > 0): ?>
              <?php while ($post = $posts->fetch_assoc()): ?>
                <?php
                $hasImg = !empty($post['image']);
                $detailHtml = '';
                if ($hasImg) {
                    $detailHtml .= '<img src="../profile/' . htmlspecialchars($post['image']) . '" alt="" class="detail-image">';
                }
                $detailHtml .= '<h2 style="margin:0 0 8px;font-size:1.3rem;font-weight:700;color:#1a2332;">' . htmlspecialchars($post['produit'] ?: $post['title'] ?: 'Bon plan') . '</h2>';
                $detailHtml .= '<div style="font-size:0.85rem;color:#888;margin-bottom:16px;">' . htmlspecialchars($post['prenom'] . ' ' . $post['nom']) . ' · ' . timeAgo($post['created_at']) . '</div>';
                if (!empty($post['bonplan_category'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">Catégorie</div><div class="detail-field-value">' . htmlspecialchars($post['bonplan_category']) . '</div></div>';
                }
                $detailHtml .= '<div class="detail-content">' . nl2br(htmlspecialchars($post['content'])) . '</div>';
                if (!empty($post['etat'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">État</div><div class="detail-field-value">' . htmlspecialchars($post['etat']) . '</div></div>';
                }
                if (!empty($post['location'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">Ville</div><div class="detail-field-value">' . htmlspecialchars($post['location']) . '</div></div>';
                }
                if (!empty($post['price'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">Prix</div><div class="detail-field-value">' . htmlspecialchars($post['price']) . ' €</div></div>';
                }
                ?>
                <article class="bp-card reveal <?php echo $hasImg ? '' : 'no-image'; ?>" onclick="openPostDetail(this)" data-detail-html="<?php echo base64_encode($detailHtml); ?>">
                  <?php if ($hasImg): ?>
                    <div class="bp-card-img-wrap">
                      <img src="../profile/<?php echo htmlspecialchars($post['image']); ?>" alt="" class="bp-slide active" />
                      <span class="bp-tag-price">Bon plan</span>
                    </div>
                  <?php endif; ?>
                  <div class="bp-card-body">
                    <h3><?php echo htmlspecialchars($post['produit'] ?: $post['title'] ?: 'Bon plan'); ?></h3>
                    <p class="bp-card-loc"><?php echo htmlspecialchars(substr($post['content'],0,120)); ?>...</p>
                    <div class="bp-card-meta">
                      <span><?php echo htmlspecialchars($post['prenom'].' '.$post['nom']); ?></span>
                      <span><?php echo timeAgo($post['created_at']); ?></span>
                    </div>
                    <?php if ($post['user_id'] == $user_id): ?>
                      <form method="POST" action="../profile/delete_post.php" style="margin-top:0.5rem;" onsubmit="return confirm('Delete this post?');" onclick="event.stopPropagation();">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>" />
                        <button type="submit" style="background:none;border:none;color:#EF4444;cursor:pointer;font-size:0.8rem;padding:0;">🗑️ Delete</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endwhile; ?>
            <?php endif; ?>

            <?php if ($posts->num_rows === 0): ?>
              <p style="text-align:center;color:#888;padding:2rem;">Aucune annonce trouvée pour ces critères.</p>
            <?php endif; ?>
          </div>
        </section>

        <!-- STATS -->
        <section class="bp-stats reveal">
          <div class="bp-stat">
            <span class="bp-stat-num">2 400</span>
            <span class="bp-stat-label">annonces actives</span>
          </div>
          <div class="bp-stat">
            <span class="bp-stat-num">850</span>
            <span class="bp-stat-label">étudiants vendeurs</span>
          </div>
          <div class="bp-stat">
            <span class="bp-stat-num">&lt; 24h</span>
            <span class="bp-stat-label">délai moyen de réponse</span>
          </div>
        </section>
      </section>
    </main>
  </div>

  <script>
    (function () {
      const hero = document.querySelector("[data-hero-slider]");
      if (hero) {
        const slides = hero.querySelectorAll(".bp-hero-slide");
        const dotsWrap = hero.querySelector(".bp-hero-dots");

        if (slides.length && dotsWrap) {
          let index = 0;
          let timer = null;

          slides.forEach((_, i) => {
            const dot = document.createElement("span");
            dot.className = "bp-hero-dot" + (i === 0 ? " active" : "");
            dot.addEventListener("click", () => { index = i; renderHero(); restartHero(); });
            dotsWrap.appendChild(dot);
          });

          const dots = dotsWrap.querySelectorAll(".bp-hero-dot");

          function renderHero() {
            slides.forEach((slide, i) => slide.classList.toggle("active", i === index));
            dots.forEach((dot, i) => dot.classList.toggle("active", i === index));
          }
          function nextHero() { index = (index + 1) % slides.length; renderHero(); }
          function startHero() { timer = setInterval(nextHero, 3500); }
          function stopHero() { clearInterval(timer); timer = null; }
          function restartHero() { stopHero(); startHero(); }

          hero.addEventListener("mouseenter", stopHero);
          hero.addEventListener("mouseleave", startHero);
          hero.addEventListener("touchstart", stopHero, { passive: true });
          hero.addEventListener("touchend", startHero);

          renderHero();
          startHero();
        }
      }

      const cardSliders = document.querySelectorAll("[data-slider]");
      cardSliders.forEach((slider) => {
        const slides = slider.querySelectorAll(".bp-slide");
        const dotsWrap = slider.querySelector(".bp-slide-dots");
        if (!slides.length || !dotsWrap) return;

        let index = 0;
        let timer = null;

        slides.forEach((_, i) => {
          const dot = document.createElement("span");
          dot.className = "bp-slide-dot" + (i === 0 ? " active" : "");
          dot.addEventListener("click", () => { index = i; renderCard(); });
          dotsWrap.appendChild(dot);
        });

        const dots = dotsWrap.querySelectorAll(".bp-slide-dot");

        function renderCard() {
          slides.forEach((slide, i) => slide.classList.toggle("active", i === index));
          dots.forEach((dot, i) => dot.classList.toggle("active", i === index));
        }
        function nextCard() { index = (index + 1) % slides.length; renderCard(); }
        function startOnHover() { if (timer) return; timer = setInterval(nextCard, 1200); }
        function stopOnLeave() { clearInterval(timer); timer = null; index = 0; renderCard(); }

        slider.addEventListener("mouseenter", startOnHover);
        slider.addEventListener("mouseleave", stopOnLeave);
        slider.addEventListener("touchstart", startOnHover, { passive: true });
        slider.addEventListener("touchend", stopOnLeave);

        renderCard();
      });

      const resetBtn = document.querySelector(".bp-filter-btn.reset");
      if (resetBtn) {
        resetBtn.addEventListener("click", () => {
          document.querySelectorAll(".bp-select").forEach((s) => (s.value = ""));
        });
      }

      const chips = document.querySelectorAll(".bp-chip");
      chips.forEach((chip) => {
        chip.addEventListener("click", () => {
          chips.forEach((c) => c.classList.remove("active"));
          chip.classList.add("active");
        });
      });

      const revealTargets = document.querySelectorAll(".reveal, .reveal-left, .reveal-right");
      if (revealTargets.length) {
        const revealObserver = new IntersectionObserver(
          (entries, obs) => {
            entries.forEach((entry) => {
              if (entry.isIntersecting) {
                entry.target.classList.add("is-visible");
                obs.unobserve(entry.target);
              }
            });
          },
          { threshold: 0.15, rootMargin: "0px 0px -40px 0px" }
        );
        revealTargets.forEach((el) => revealObserver.observe(el));
      }
    })();

    function openPostDetail(el) {
      const modal = document.getElementById('post-detail-modal');
      const body  = document.getElementById('detail-body-content');
      /* Unicode-safe base64 decode (atob breaks accents) */
      body.innerHTML = decodeURIComponent(escape(atob(el.getAttribute('data-detail-html'))));
      modal.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    function closePostDetail() {
      document.getElementById('post-detail-modal').classList.remove('active');
      document.body.style.overflow = '';
    }
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closePostDetail();
    });
  </script>

  <!-- Post Detail Modal -->
  <div class="post-detail-modal" id="post-detail-modal">
    <div class="detail-overlay" onclick="closePostDetail()"></div>
    <div class="detail-card">
      <button class="detail-close" onclick="closePostDetail()">×</button>
      <div id="detail-body-content" style="padding:24px;"></div>
    </div>
  </div>

  <?php $widget_base_path = '../'; include '../includes/chat_widget.php'; ?>

</body>
</html>
