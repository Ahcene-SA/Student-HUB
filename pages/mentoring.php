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
$stmt = $conn->prepare("
    SELECT p.*, u.prenom, u.nom, u.username, u.avatar
    FROM posts p JOIN user u ON u.id_user = p.user_id
    WHERE p.category = 'mentoring' ORDER BY p.created_at DESC
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
  <title>Student HUB – Mentoring</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700&family=Nunito:wght@400;500;600;700;800;900&family=Caveat:wght@500;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="mentoring.css" />
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
    .mnt-card { cursor: pointer; transition: transform 0.15s ease, box-shadow 0.2s ease; }
    .mnt-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.12); }
    /* Card image area (user avatar circle) */
    .mnt-card-img-wrap {
      width: 100%; padding: 24px 0 8px;
      display: flex; align-items: center; justify-content: center;
      background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    }
    .mnt-card-img-circle {
      width: 120px; height: 120px; border-radius: 50%;
      object-fit: cover;
      border: 4px solid #fff;
      box-shadow: 0 6px 20px rgba(22,101,52,0.22);
    }
    .mnt-card-img-initials {
      width: 120px; height: 120px; border-radius: 50%;
      background: linear-gradient(135deg, #166534, #15803D);
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-weight: 800; font-size: 2rem;
      border: 4px solid #fff;
      box-shadow: 0 6px 20px rgba(22,101,52,0.22);
    }
    /* Mentor badge */
    .mnt-badge {
      display: inline-block;
      background: linear-gradient(135deg, #166534, #15803D);
      color: #fff;
      font-size: 0.65rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      padding: 3px 10px;
      border-radius: 999px;
    }
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
        <a href="mentoring.php" class="active">Mentoring</a>
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

    <!-- PAGE MENTORING -->
    <main class="mnt-page">
      <section class="mnt-shell">

        <!-- HERO -->
        <div class="mnt-hero-card reveal" data-hero-slider>
          <img src="../img/mentor/083a43dfff7e45e21d64f17ac34c5a9f.jpg" alt="Mentor et étudiant" class="mnt-hero-slide active" />
          <img src="../img/mentor/aa54de56afff1eee75c1165e7831815d.jpg" alt="Session de mentoring" class="mnt-hero-slide" />
          <img src="../img/mentor/dd45bb696dcc93d8fce9dcf505e24e36.jpg" alt="Étudiants apprenant" class="mnt-hero-slide" />

          <div class="mnt-hero-gradient"></div>
          <div class="mnt-hero-text">
            <span class="mnt-hero-eyebrow">Tutorat · Mentorat · Apprentissage</span>
            <h1>Apprends avec un <span class="mnt-hl">mentor</span>.</h1>
            <p>Des étudiants avancés t'accompagnent — gratuitement, à ton rythme, et sur ce qui compte vraiment pour ta réussite.</p>
            <div class="mnt-hero-meta">
              <span><strong>340</strong> mentors actifs</span>
              <span class="mnt-hero-sep"></span>
              <span><strong>1 800</strong> sessions</span>
              <span class="mnt-hero-sep"></span>
              <span><strong>4.9/5</strong> ⭐</span>
            </div>
          </div>

          <div class="mnt-hero-dots"></div>
        </div>

        <!-- LEARNING JOURNEY -->
        <nav class="mnt-journey reveal" aria-label="Parcours d'apprentissage">
          <div class="mnt-journey-step is-active">
            <span class="mnt-journey-num">01</span>
            <div class="mnt-journey-text">
              <strong>Choisis ton mentor</strong>
              <span>Filtre par matière, niveau et disponibilité.</span>
            </div>
          </div>
          <span class="mnt-journey-arrow" aria-hidden="true">→</span>
          <div class="mnt-journey-step">
            <span class="mnt-journey-num">02</span>
            <div class="mnt-journey-text">
              <strong>Réserve une session</strong>
              <span>Une heure gratuite — en ligne ou en présentiel.</span>
            </div>
          </div>
          <span class="mnt-journey-arrow" aria-hidden="true">→</span>
          <div class="mnt-journey-step">
            <span class="mnt-journey-num">03</span>
            <div class="mnt-journey-text">
              <strong>Apprenez ensemble</strong>
              <span>Cours personnalisé · ressources partagées.</span>
            </div>
          </div>
          <span class="mnt-journey-arrow" aria-hidden="true">→</span>
          <div class="mnt-journey-step">
            <span class="mnt-journey-num">04</span>
            <div class="mnt-journey-text">
              <strong>Progresse</strong>
              <span>Suivi continu · objectifs validés ensemble.</span>
            </div>
          </div>
        </nav>

        <!-- TOP BAR -->
        <div class="mnt-top-bar reveal">
          <div class="mnt-search-row">
            <input type="text" class="mnt-search-input" placeholder="Matière, université, langue..." />
            <button type="button" class="mnt-search-btn">Trouver un mentor</button>
          </div>
        </div>

        <!-- FILTERS -->
        <div class="mnt-filters-board reveal">
          <div class="mnt-filter-item">
            <label for="f-domaine">Matière</label>
            <select id="f-domaine" class="mnt-select">
              <option value="">Choisir</option>
              <option value="info">Informatique</option>
              <option value="gc">Génie Civil</option>
              <option value="math">Mathématiques</option>
            </select>
          </div>

          <div class="mnt-filter-item">
            <label for="f-niveau">Niveau</label>
            <select id="f-niveau" class="mnt-select">
              <option value="">Choisir</option>
              <option value="l1">Licence 1</option>
              <option value="l3">Licence 3</option>
              <option value="m2">Master 2</option>
            </select>
          </div>

          <div class="mnt-filter-item">
            <label for="f-dispo">Disponibilité</label>
            <select id="f-dispo" class="mnt-select">
              <option value="">Choisir</option>
              <option value="week">Cette semaine</option>
              <option value="weekend">Week-end</option>
              <option value="evening">Soirées</option>
            </select>
          </div>

          <div class="mnt-filter-item">
            <label for="f-langue">Langue</label>
            <select id="f-langue" class="mnt-select">
              <option value="">Choisir</option>
              <option value="fr">Français</option>
              <option value="ar">Arabe</option>
              <option value="en">Anglais</option>
            </select>
          </div>

          <div class="mnt-filter-actions">
            <button type="button" class="mnt-filter-btn apply">Appliquer</button>
            <button type="button" class="mnt-filter-btn reset">Reset</button>
          </div>
        </div>

        <!-- LIST -->
        <section class="mnt-list">
          <header class="mnt-list-header reveal">
            <div>
              <h2>Mentors recommandés</h2>
              <p>Des étudiants avancés certifiés par notre communauté.</p>
            </div>
            <div class="mnt-list-meta">
              <span class="mnt-result-count"><strong>340</strong> mentors disponibles</span>
            </div>
          </header>

          <div class="mnt-cards">
            <!-- REAL POSTS -->
            <?php if ($posts->num_rows > 0): ?>
              <?php while ($post = $posts->fetch_assoc()): ?>
                <?php
                $hasImg = !empty($post['image']);
                $detailHtml = '';
                if ($hasImg) {
                    $detailHtml .= '<img src="../profile/' . htmlspecialchars($post['image']) . '" alt="" class="detail-image">';
                }
                $detailHtml .= '<h2 style="margin:0 0 8px;font-size:1.3rem;font-weight:700;color:#1a2332;">' . htmlspecialchars($post['title'] ?: 'Mentorat') . '</h2>';
                $detailHtml .= '<div style="font-size:0.85rem;color:#888;margin-bottom:16px;">' . htmlspecialchars($post['prenom'] . ' ' . $post['nom']) . ' · ' . timeAgo($post['created_at']) . '</div>';
                $detailHtml .= '<div class="detail-content">' . nl2br(htmlspecialchars($post['content'])) . '</div>';
                if (!empty($post['matiere'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">Matière</div><div class="detail-field-value">' . htmlspecialchars($post['matiere']) . '</div></div>';
                }
                if (!empty($post['niveau_mentoring'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">Niveau</div><div class="detail-field-value">' . htmlspecialchars($post['niveau_mentoring']) . '</div></div>';
                }
                if (!empty($post['langue'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">Langue</div><div class="detail-field-value">' . htmlspecialchars($post['langue']) . '</div></div>';
                }
                if (!empty($post['disponibilite'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">Disponibilité</div><div class="detail-field-value">' . htmlspecialchars($post['disponibilite']) . '</div></div>';
                }
                if (!empty($post['is_mentor'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">Rôle</div><div class="detail-field-value">Mentor</div></div>';
                }
                if (!empty($post['prix_mentoring'])) {
                    $detailHtml .= '<div class="detail-field"><div class="detail-field-label">Prix</div><div class="detail-field-value">' . htmlspecialchars($post['prix_mentoring']) . ' €/heure</div></div>';
                }
                ?>
                <article class="mnt-card reveal" onclick="openPostDetail(this)" data-detail-html="<?php echo base64_encode($detailHtml); ?>">
                  <!-- User avatar as circled image -->
                  <div class="mnt-card-img-wrap">
                    <?php if (!empty($post['avatar'])): ?>
                      <img src="../profile/<?php echo htmlspecialchars($post['avatar']); ?>" alt="" class="mnt-card-img-circle" />
                    <?php else: ?>
                      <div class="mnt-card-img-initials"><?php echo strtoupper(substr($post['prenom'],0,1) . substr($post['nom'],0,1)); ?></div>
                    <?php endif; ?>
                  </div>
                  <div class="mnt-card-body">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                      <h3 style="margin:0;"><?php echo htmlspecialchars($post['prenom'].' '.$post['nom']); ?></h3>
                      <?php if (!empty($post['is_mentor'])): ?>
                        <span class="mnt-badge">Mentor</span>
                      <?php endif; ?>
                    </div>
                    <p class="mnt-card-field"><?php echo htmlspecialchars($post['title'] ?: 'Mentorat'); ?></p>
                    <p style="font-size:0.85rem;color:#555;margin:0.5rem 0;"><?php echo htmlspecialchars(substr($post['content'],0,120)); ?>...</p>
                    <div class="mnt-rating">
                      <span style="font-size:0.8rem;color:#888;"><?php echo timeAgo($post['created_at']); ?></span>
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
              <p style="text-align:center;color:#888;padding:2rem;">Aucun mentor pour le moment.</p>
            <?php endif; ?>
          </div>
        </section>

        <!-- TESTIMONIALS -->
        <section class="mnt-testimonials reveal">
          <header class="mnt-tm-head">
            <span class="mnt-tm-handwritten">Témoignages</span>
            <h2>Ils ont avancé grâce à un mentor.</h2>
          </header>

          <div class="mnt-tm-grid">
            <blockquote class="mnt-tm">
              <span class="mnt-tm-quote">"</span>
              <p>Grâce à ma mentor, j'ai validé mon module d'algo avec une mention. Pédagogue et patiente — exactement ce qu'il me fallait.</p>
              <footer>
                <span class="mnt-tm-avatar">SD</span>
                <div>
                  <strong>Sara Djebali</strong>
                  <span>Licence 2 · USTHB</span>
                </div>
              </footer>
            </blockquote>

            <blockquote class="mnt-tm">
              <span class="mnt-tm-quote">"</span>
              <p>3 sessions, et mon mémoire avait enfin une structure. Karim m'a appris à découper un problème complexe — je l'utilise encore.</p>
              <footer>
                <span class="mnt-tm-avatar">YH</span>
                <div>
                  <strong>Yacine Hammadi</strong>
                  <span>Master 2 · USTO</span>
                </div>
              </footer>
            </blockquote>

            <blockquote class="mnt-tm">
              <span class="mnt-tm-quote">"</span>
              <p>J'avais perdu confiance en physique. Mon mentor m'a accompagnée chaque semaine — résultat : 16/20 à l'examen final.</p>
              <footer>
                <span class="mnt-tm-avatar">AB</span>
                <div>
                  <strong>Amel Belhadj</strong>
                  <span>Licence 1 · USTHB</span>
                </div>
              </footer>
            </blockquote>
          </div>
        </section>

        <!-- SUBJECTS GRID -->
        <section class="mnt-subjects-section reveal">
          <header class="mnt-subj-head">
            <h2>Matières couvertes</h2>
            <p>Plus de 60 disciplines disponibles — du Lycée au Master.</p>
          </header>

          <div class="mnt-subj-grid">
            <a href="#" class="mnt-subj-card">
              <span class="mnt-subj-icon">∑</span>
              <strong>Mathématiques</strong>
              <span class="mnt-subj-count">87 mentors</span>
            </a>
            <a href="#" class="mnt-subj-card">
              <span class="mnt-subj-icon">&lt;/&gt;</span>
              <strong>Informatique</strong>
              <span class="mnt-subj-count">112 mentors</span>
            </a>
            <a href="#" class="mnt-subj-card">
              <span class="mnt-subj-icon">⚛</span>
              <strong>Physique</strong>
              <span class="mnt-subj-count">54 mentors</span>
            </a>
            <a href="#" class="mnt-subj-card">
              <span class="mnt-subj-icon">⌂</span>
              <strong>Génie Civil</strong>
              <span class="mnt-subj-count">38 mentors</span>
            </a>
            <a href="#" class="mnt-subj-card">
              <span class="mnt-subj-icon">A</span>
              <strong>Langues</strong>
              <span class="mnt-subj-count">49 mentors</span>
            </a>
            <a href="#" class="mnt-subj-card">
              <span class="mnt-subj-icon">€</span>
              <strong>Économie</strong>
              <span class="mnt-subj-count">22 mentors</span>
            </a>
          </div>
        </section>

        <!-- STATS -->
        <section class="mnt-stats reveal">
          <div class="mnt-stat">
            <span class="mnt-stat-num">340</span>
            <span class="mnt-stat-label">mentors actifs</span>
          </div>
          <div class="mnt-stat">
            <span class="mnt-stat-num">1 800</span>
            <span class="mnt-stat-label">sessions réalisées</span>
          </div>
          <div class="mnt-stat alt">
            <span class="mnt-stat-num">4.9<small>/5</small></span>
            <span class="mnt-stat-label">note moyenne</span>
          </div>
        </section>
      </section>
    </main>
  </div>

  <script>
    (function () {
      const hero = document.querySelector("[data-hero-slider]");
      if (hero) {
        const slides = hero.querySelectorAll(".mnt-hero-slide");
        const dotsWrap = hero.querySelector(".mnt-hero-dots");

        if (slides.length && dotsWrap) {
          let index = 0;
          let timer = null;

          slides.forEach((_, i) => {
            const dot = document.createElement("span");
            dot.className = "mnt-hero-dot" + (i === 0 ? " active" : "");
            dot.addEventListener("click", () => { index = i; renderHero(); restartHero(); });
            dotsWrap.appendChild(dot);
          });

          const dots = dotsWrap.querySelectorAll(".mnt-hero-dot");

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

      const resetBtn = document.querySelector(".mnt-filter-btn.reset");
      if (resetBtn) {
        resetBtn.addEventListener("click", () => {
          document.querySelectorAll(".mnt-select").forEach((s) => (s.value = ""));
        });
      }

      const chips = document.querySelectorAll(".mnt-chip");
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
