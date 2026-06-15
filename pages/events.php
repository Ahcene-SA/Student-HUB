<?php
session_start();
$user_id = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;

require_once __DIR__ . '/../includes/db_config.php';
$conn = get_db_connection();
$conn->query("
    CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, category VARCHAR(30) DEFAULT 'general',
        title VARCHAR(255), content TEXT NOT NULL, image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user(user_id), INDEX idx_category(category), INDEX idx_created(created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
$stmt = $conn->prepare("
    SELECT p.*, u.prenom, u.nom, u.username, u.avatar
    FROM posts p JOIN user u ON u.id_user = p.user_id
    WHERE p.category = 'events' ORDER BY p.created_at DESC
");
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
  <title>Student HUB – Events</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@500;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="events.css" />
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
    .ev-card { cursor: pointer; transition: transform 0.15s ease, box-shadow 0.2s ease; }
    .ev-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.12); }
    /* Text-only cards (no image) */
    .ev-card.no-image .ev-card-img-wrap { display: none; }
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
        <a href="events.php" class="active">Events</a>
        <a href="mentoring.php">Mentoring</a>
        <a href="bonplan.php">Bons plans</a>
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

    <!-- PAGE EVENTS -->
    <main class="ev-page">
      <section class="ev-shell">

        <!-- HERO -->
        <div class="ev-hero-card reveal" data-hero-slider>
          <img src="../img/event/27ecd9ca24b0d23757832d056c5aab9c.jpg" alt="Soirée étudiante" class="ev-hero-slide active" />
          <img src="../img/event/165f70c25a09b31ea1806fadae59a511.jpg" alt="Concert" class="ev-hero-slide" />
          <img src="../img/event/1a5edf62aeded336ff159ab423ae273d.jpg" alt="Festival" class="ev-hero-slide" />

          <div class="ev-hero-gradient"></div>
          <div class="ev-hero-text">
            <h1>Events</h1>
            <p>Soirées, concerts, hackathons — vis chaque moment fort de la vie étudiante.</p>
          </div>

          <div class="ev-hero-dots"></div>
        </div>

        <!-- TOP BAR -->
        <div class="ev-top-bar reveal">
          <div class="ev-search-row">
            <input type="text" class="ev-search-input" placeholder="Nom d'event, artiste, ville..." />
            <button type="button" class="ev-search-btn">Rechercher</button>
          </div>

          <div class="ev-chips">
            <button type="button" class="ev-chip active">Soirées</button>
            <button type="button" class="ev-chip">Conférences</button>
            <button type="button" class="ev-chip">Hackathons</button>
            <button type="button" class="ev-chip">Ateliers</button>
          </div>
        </div>

        <!-- FILTERS -->
        <div class="ev-filters-board reveal">
          <div class="ev-filter-item">
            <label for="f-ville">Ville</label>
            <select id="f-ville" class="ev-select">
              <option value="">Choisir</option>
              <option value="alger">Alger</option>
              <option value="oran">Oran</option>
              <option value="constantine">Constantine</option>
            </select>
          </div>

          <div class="ev-filter-item">
            <label for="f-date">Date</label>
            <select id="f-date" class="ev-select">
              <option value="">Choisir</option>
              <option value="today">Aujourd'hui</option>
              <option value="week">Cette semaine</option>
              <option value="month">Ce mois</option>
            </select>
          </div>

          <div class="ev-filter-item">
            <label for="f-cat">Catégorie</label>
            <select id="f-cat" class="ev-select">
              <option value="">Choisir</option>
              <option value="party">Soirée</option>
              <option value="hack">Hackathon</option>
              <option value="conf">Conférence</option>
            </select>
          </div>

          <div class="ev-filter-item">
            <label for="f-prix">Prix</label>
            <select id="f-prix" class="ev-select">
              <option value="">Choisir</option>
              <option value="free">Gratuit</option>
              <option value="1000">≤ 1 000 DA</option>
              <option value="3000">≤ 3 000 DA</option>
            </select>
          </div>

          <div class="ev-filter-actions">
            <button type="button" class="ev-filter-btn apply">Appliquer</button>
            <button type="button" class="ev-filter-btn reset">Reset</button>
          </div>
        </div>

        <!-- LIST -->
        <section class="ev-list">
          <header class="ev-list-header reveal">
            <h2>Events à ne pas rater</h2>
            <p>Sélection chaude par la communauté Student HUB.</p>
          </header>

          <div class="ev-cards">
            <!-- REAL POSTS -->
            <?php if ($posts->num_rows > 0): ?>
              <?php while ($post = $posts->fetch_assoc()): ?>
                <?php
                $hasImg = !empty($post['image']);
                $detailHtml = '';
                if ($hasImg) {
                    $detailHtml .= '<img src="../profile/' . htmlspecialchars($post['image']) . '" alt="" class="detail-image">';
                }
                $detailHtml .= '<h2 style="margin:0 0 8px;font-size:1.3rem;font-weight:700;color:#1a2332;">' . htmlspecialchars($post['title'] ?: 'Événement') . '</h2>';
                $detailHtml .= '<div style="font-size:0.85rem;color:#888;margin-bottom:16px;">' . htmlspecialchars($post['prenom'] . ' ' . $post['nom']) . ' · ' . timeAgo($post['created_at']) . '</div>';
                $detailHtml .= '<div class="detail-content">' . nl2br(htmlspecialchars($post['content'])) . '</div>';
                if (!empty($post['location'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">Ville</div><div class="detail-field-value">' . htmlspecialchars($post['location']) . '</div></div>';
                }
                if (!empty($post['type_event'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">Type d\'événement</div><div class="detail-field-value">' . htmlspecialchars($post['type_event']) . '</div></div>';
                }
                if (!empty($post['event_date'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">Date</div><div class="detail-field-value">' . htmlspecialchars($post['event_date']) . '</div></div>';
                }
                if (!empty($post['is_free'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">Tarif</div><div class="detail-field-value">Gratuit</div></div>';
                } elseif (!empty($post['tarif'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">Tarif</div><div class="detail-field-value">' . htmlspecialchars($post['tarif']) . ' €</div></div>';
                }
                ?>
                <article class="ev-card reveal <?php echo $hasImg ? '' : 'no-image'; ?>" onclick="openPostDetail(this)" data-detail-html="<?php echo base64_encode($detailHtml); ?>">
                  <?php if ($hasImg): ?>
                    <div class="ev-card-img-wrap">
                      <img src="../profile/<?php echo htmlspecialchars($post['image']); ?>" alt="" class="ev-slide active" />
                      <span class="ev-tag-price">Event</span>
                    </div>
                  <?php endif; ?>
                  <div class="ev-card-body">
                    <h3><?php echo htmlspecialchars($post['title'] ?: 'Événement'); ?></h3>
                    <p class="ev-card-loc"><?php echo htmlspecialchars(substr($post['content'],0,120)); ?>...</p>
                    <div class="ev-card-meta">
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
              <p style="text-align:center;color:#888;padding:2rem;">Aucun événement pour le moment.</p>
            <?php endif; ?>
          </div>
        </section>

        <!-- STATS -->
        <section class="ev-stats reveal">
          <div class="ev-stat">
            <span class="ev-stat-num">86</span>
            <span class="ev-stat-label">events ce mois</span>
          </div>
          <div class="ev-stat">
            <span class="ev-stat-num">3 200</span>
            <span class="ev-stat-label">participants</span>
          </div>
          <div class="ev-stat">
            <span class="ev-stat-num">12</span>
            <span class="ev-stat-label">villes couvertes</span>
          </div>
        </section>
      </section>
    </main>
  </div>

  <script>
    (function () {
      const hero = document.querySelector("[data-hero-slider]");
      if (hero) {
        const slides = hero.querySelectorAll(".ev-hero-slide");
        const dotsWrap = hero.querySelector(".ev-hero-dots");

        if (slides.length && dotsWrap) {
          let index = 0;
          let timer = null;

          slides.forEach((_, i) => {
            const dot = document.createElement("span");
            dot.className = "ev-hero-dot" + (i === 0 ? " active" : "");
            dot.addEventListener("click", () => { index = i; renderHero(); restartHero(); });
            dotsWrap.appendChild(dot);
          });

          const dots = dotsWrap.querySelectorAll(".ev-hero-dot");

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
        const slides = slider.querySelectorAll(".ev-slide");
        const dotsWrap = slider.querySelector(".ev-slide-dots");
        if (!slides.length || !dotsWrap) return;

        let index = 0;
        let timer = null;

        slides.forEach((_, i) => {
          const dot = document.createElement("span");
          dot.className = "ev-slide-dot" + (i === 0 ? " active" : "");
          dot.addEventListener("click", () => { index = i; renderCard(); });
          dotsWrap.appendChild(dot);
        });

        const dots = dotsWrap.querySelectorAll(".ev-slide-dot");

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

      const resetBtn = document.querySelector(".ev-filter-btn.reset");
      if (resetBtn) {
        resetBtn.addEventListener("click", () => {
          document.querySelectorAll(".ev-select").forEach((s) => (s.value = ""));
        });
      }

      const chips = document.querySelectorAll(".ev-chip");
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
