<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 19 Nov 1981 08:52:00 GMT");
$user_id = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;

$conn = new mysqli("127.0.0.1", "root", "root", "studenthub", 8889);
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
    WHERE p.category = 'stage' ORDER BY p.created_at DESC
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
  <title>Student HUB – Stage &amp; Alternance</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="stage.css" />
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
    .stg-card { cursor: pointer; transition: transform 0.15s ease, box-shadow 0.2s ease; }
    .stg-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.12); }
    /* Text-only cards (no image) */
    .stg-card.no-image .stg-card-img-wrap { display: none; }
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
        <a href="stage.php" class="active">Stage</a>
        <a href="events.php">Events</a>
        <a href="mentoring.php">Mentoring</a>
        <a href="bonplan.php">Bons plans</a>
        <div class="user-menu">
          <button class="post_btn user-menu-trigger" tabindex="0">
            Mon compte ▾
          </button>
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

    <!-- TOP RIBBON -->
    <div class="stg-ribbon">
      <div class="stg-ribbon-inner">
        <span class="stg-ribbon-dot"></span>
        <span class="stg-ribbon-text">Portail officiel · Conventions de stage validées par les universités partenaires</span>
        <span class="stg-ribbon-spacer"></span>
        <span class="stg-ribbon-meta">Mis à jour · Auj. 09:42</span>
      </div>
    </div>

    <!-- PAGE STAGE -->
    <main class="stg-page">
      <section class="stg-shell">

        <!-- HERO -->
        <div class="stg-hero-card reveal" data-hero-slider>
          <img src="../img/stage/557b5e65fd9e202b8c66192b53e5e1d7.jpg" alt="Bureau corporate" class="stg-hero-slide active" />
          <img src="../img/stage/88c2a619772b9141295e766ca769cd55.jpg" alt="Équipe en réunion" class="stg-hero-slide" />
          <img src="../img/stage/e043688dbd21ad747965373b9d407fec.jpg" alt="Espace de coworking" class="stg-hero-slide" />

          <div class="stg-hero-gradient"></div>
          <div class="stg-hero-text">
            <span class="stg-hero-eyebrow">Carrière · Stage · Alternance</span>
            <h1>Lance ta carrière.</h1>
            <p>Des offres vérifiées, des conventions reconnues et des entreprises qui recrutent réellement.</p>
            <div class="stg-hero-meta">
              <span><strong>4 800</strong> offres ouvertes</span>
              <span class="stg-hero-sep"></span>
              <span><strong>1 100</strong> entreprises</span>
              <span class="stg-hero-sep"></span>
              <span><strong>72%</strong> embauche post-stage</span>
            </div>
          </div>

          <div class="stg-hero-dots"></div>
        </div>

        <!-- CAREER STEPPER -->
        <nav class="stg-stepper reveal" aria-label="Parcours candidat">
          <div class="stg-step is-active">
            <span class="stg-step-num">01</span>
            <div class="stg-step-text">
              <strong>Découverte</strong>
              <span>Filtre les offres adaptées à ton profil</span>
            </div>
          </div>
          <div class="stg-step-line"></div>
          <div class="stg-step">
            <span class="stg-step-num">02</span>
            <div class="stg-step-text">
              <strong>Candidature</strong>
              <span>Postule en un clic avec ton CV vérifié</span>
            </div>
          </div>
          <div class="stg-step-line"></div>
          <div class="stg-step">
            <span class="stg-step-num">03</span>
            <div class="stg-step-text">
              <strong>Convention</strong>
              <span>Signature électronique · établissement validé</span>
            </div>
          </div>
          <div class="stg-step-line"></div>
          <div class="stg-step">
            <span class="stg-step-num">04</span>
            <div class="stg-step-text">
              <strong>Mission</strong>
              <span>Suivi mensuel et évaluation finale</span>
            </div>
          </div>
        </nav>

        <!-- TOP BAR -->
        <div class="stg-top-bar reveal">
          <div class="stg-search-row">
            <input type="text" class="stg-search-input" placeholder="Métier, entreprise, ville..." />
            <button type="button" class="stg-search-btn">Rechercher</button>
          </div>

          <div class="stg-chips">
            <button type="button" class="stg-chip active">Informatique</button>
            <button type="button" class="stg-chip">Marketing</button>
            <button type="button" class="stg-chip">Finance</button>
            <button type="button" class="stg-chip">Design</button>
            <button type="button" class="stg-chip">Ingénierie</button>
          </div>
        </div>

        <!-- FILTERS -->
        <div class="stg-filters-board reveal">
          <div class="stg-filter-item">
            <label for="f-domaine">Domaine</label>
            <select id="f-domaine" class="stg-select">
              <option value="">Choisir</option>
              <option value="info">Informatique</option>
              <option value="mkt">Marketing</option>
              <option value="fin">Finance</option>
            </select>
          </div>

          <div class="stg-filter-item">
            <label for="f-duree">Durée</label>
            <select id="f-duree" class="stg-select">
              <option value="">Choisir</option>
              <option value="2">2 mois</option>
              <option value="4">4 mois</option>
              <option value="6">6 mois</option>
              <option value="12">12 mois</option>
            </select>
          </div>

          <div class="stg-filter-item">
            <label for="f-ville">Ville</label>
            <select id="f-ville" class="stg-select">
              <option value="">Choisir</option>
              <option value="alger">Alger</option>
              <option value="oran">Oran</option>
              <option value="remote">Remote</option>
            </select>
          </div>

          <div class="stg-filter-item">
            <label for="f-type">Type</label>
            <select id="f-type" class="stg-select">
              <option value="">Choisir</option>
              <option value="stage">Stage</option>
              <option value="alt">Alternance</option>
            </select>
          </div>

          <div class="stg-filter-actions">
            <button type="button" class="stg-filter-btn apply">Appliquer</button>
            <button type="button" class="stg-filter-btn reset">Reset</button>
          </div>
        </div>

        <!-- LIST -->
        <section class="stg-list">
          <header class="stg-list-header reveal">
            <div>
              <h2>Offres recommandées</h2>
              <p>Entreprises vérifiées · Conventions de stage validées · Mise à jour quotidienne.</p>
            </div>
            <div class="stg-list-meta">
              <span class="stg-result-count"><strong>4 800</strong> résultats</span>
              <span class="stg-sort">Trier : <strong>Pertinence</strong></span>
            </div>
          </header>

          <div class="stg-cards">
            <!-- REAL POSTS -->
            <?php if ($posts->num_rows > 0): ?>
              <?php while ($post = $posts->fetch_assoc()): ?>
                <?php
                $hasImg = !empty($post['image']);
                $detailHtml = '';
                if ($hasImg) {
                    $detailHtml .= '<img src="../profile/' . htmlspecialchars($post['image']) . '" alt="" class="detail-image">';
                }
                $detailHtml .= '<h2 style="margin:0 0 8px;font-size:1.3rem;font-weight:700;color:#1a2332;">' . htmlspecialchars($post['title'] ?: 'Offre de stage') . '</h2>';
                $detailHtml .= '<div style="font-size:0.85rem;color:#888;margin-bottom:16px;">' . htmlspecialchars($post['prenom'] . ' ' . $post['nom']) . ' · ' . timeAgo($post['created_at']) . '</div>';
                $detailHtml .= '<div class="detail-content">' . nl2br(htmlspecialchars($post['content'])) . '</div>';
                if (!empty($post['domaine'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">Domaine</div><div class="detail-field-value">' . htmlspecialchars($post['domaine']) . '</div></div>';
                }
                if (!empty($post['location'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">Ville</div><div class="detail-field-value">' . htmlspecialchars($post['location']) . '</div></div>';
                }
                if (!empty($post['duree'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">Durée</div><div class="detail-field-value">' . htmlspecialchars($post['duree']) . '</div></div>';
                }
                if (!empty($post['type_stage'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">Type</div><div class="detail-field-value">' . htmlspecialchars($post['type_stage']) . '</div></div>';
                }
                if (!empty($post['niveau_etude'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">Niveau d\'étude recherché</div><div class="detail-field-value">' . htmlspecialchars($post['niveau_etude']) . '</div></div>';
                }
                if (!empty($post['company'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">Entreprise</div><div class="detail-field-value">' . htmlspecialchars($post['company']) . '</div></div>';
                }
                ?>
                <article class="stg-card reveal <?php echo $hasImg ? '' : 'no-image'; ?>" onclick="openPostDetail(this)" data-detail-html="<?php echo base64_encode($detailHtml); ?>">
                  <header class="stg-card-head">
                    <div class="stg-company-logo" data-c="b"><?php echo strtoupper(substr($post['prenom'],0,1) . substr($post['nom'],0,1)); ?></div>
                    <div class="stg-company-info">
                      <span class="stg-company-name"><?php echo htmlspecialchars($post['prenom'] . ' ' . $post['nom']); ?></span>
                      <span class="stg-company-tag">@<?php echo htmlspecialchars($post['username']); ?> · <?php echo timeAgo($post['created_at']); ?></span>
                    </div>
                  </header>
                  <?php if ($hasImg): ?>
                    <div class="stg-card-img-wrap">
                      <img src="../profile/<?php echo htmlspecialchars($post['image']); ?>" alt="" class="stg-slide active" />
                      <span class="stg-tag-type">Stage</span>
                    </div>
                  <?php endif; ?>
                  <div class="stg-card-body">
                    <h3><?php echo htmlspecialchars($post['title'] ?: 'Offre de stage'); ?></h3>
                    <p class="stg-meta-row"><span><?php echo htmlspecialchars(substr($post['content'],0,80)); ?>...</span></p>
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
              <p style="text-align:center;color:#888;padding:2rem;">Aucune offre pour le moment.</p>
            <?php endif; ?>

          </div>
        </section>

        <!-- PARTNERS -->
        <section class="stg-partners reveal">
          <header class="stg-partners-head">
            <h2>Entreprises partenaires</h2>
            <span class="stg-partners-sub">Toutes les offres ci-dessous font l'objet d'une convention reconnue.</span>
          </header>
          <div class="stg-partners-grid">
            <div class="stg-partner" data-c="b">TechCorp</div>
            <div class="stg-partner" data-c="n">Finance Group</div>
            <div class="stg-partner" data-c="y">BrandLab</div>
            <div class="stg-partner" data-c="b">Sonatrach</div>
            <div class="stg-partner" data-c="n">Sonelgaz</div>
            <div class="stg-partner" data-c="y">Yassir</div>
          </div>
        </section>

        <!-- STATS -->
        <section class="stg-stats reveal">
          <div class="stg-stat">
            <span class="stg-stat-key">STG-01</span>
            <span class="stg-stat-num">4 800</span>
            <span class="stg-stat-label">offres disponibles</span>
          </div>
          <div class="stg-stat">
            <span class="stg-stat-key">STG-02</span>
            <span class="stg-stat-num">920 DA</span>
            <span class="stg-stat-label">gratification moyenne</span>
          </div>
          <div class="stg-stat alt">
            <span class="stg-stat-key">STG-03</span>
            <span class="stg-stat-num">1 100</span>
            <span class="stg-stat-label">entreprises partenaires</span>
          </div>
        </section>
      </section>
    </main>
  </div>

  <script>
    (function () {
      const hero = document.querySelector("[data-hero-slider]");
      if (hero) {
        const slides = hero.querySelectorAll(".stg-hero-slide");
        const dotsWrap = hero.querySelector(".stg-hero-dots");

        if (slides.length && dotsWrap) {
          let index = 0;
          let timer = null;

          slides.forEach((_, i) => {
            const dot = document.createElement("span");
            dot.className = "stg-hero-dot" + (i === 0 ? " active" : "");
            dot.addEventListener("click", () => { index = i; renderHero(); restartHero(); });
            dotsWrap.appendChild(dot);
          });

          const dots = dotsWrap.querySelectorAll(".stg-hero-dot");

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
        const slides = slider.querySelectorAll(".stg-slide");
        const dotsWrap = slider.querySelector(".stg-slide-dots");
        if (!slides.length || !dotsWrap) return;

        let index = 0;
        let timer = null;

        slides.forEach((_, i) => {
          const dot = document.createElement("span");
          dot.className = "stg-slide-dot" + (i === 0 ? " active" : "");
          dot.addEventListener("click", () => { index = i; renderCard(); });
          dotsWrap.appendChild(dot);
        });

        const dots = dotsWrap.querySelectorAll(".stg-slide-dot");

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

      const resetBtn = document.querySelector(".stg-filter-btn.reset");
      if (resetBtn) {
        resetBtn.addEventListener("click", () => {
          document.querySelectorAll(".stg-select").forEach((s) => (s.value = ""));
        });
      }

      const chips = document.querySelectorAll(".stg-chip");
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
