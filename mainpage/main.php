<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="main_style.css">
    
</head>


<body>
<div class="main_page">

  <!-- NavBar -->
<nav class="navbar navbar-expand-lg bg-body-tertiary bg-dark">

  
<div class="logo_nav">

 <img src="../logo/Student_HUB_LOGO.png" alt="" class="logo">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

</div>

   

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      
      <a href="#about">About us</a>
      <a href="#services">Services</a>
      <a href="#testimonials">Testimonials</a>
      <a href="#contact">Contact us</a>
      <form action="">
        <select name="" id="">
          <option value="">FR</option>
          <option value="">EN</option>
        </select>
      </form>
      <a href="../persoinfo/signin.php"><button type="button">Sign In</button></a>
    </div>
  
</nav>

<!-- main page bg image -->
       
<div class="hero">
  <img src="image/Gemini_Generated_Image_cywkhmcywkhmcywk.png" alt="" class="bg_img" >
  <div class="overlay"></div>
  <div class="content">
    <h1>Student HUB</h1>
  <p>The first platform designed to help every student succeed — join a community that supports you every step of the way.</p>
    <a href="../persoinfo/signin.php"><button type="button">JOIN US</button></a>
  </div>
</div>

<!-- about us page -->



<div class="about_us" id="about">

  <!-- Title -->
  <div class="about_header">
    <h1>ABOUT US</h1>
    <div class="about_underline"></div>
  </div>

  <!-- Row: text + image -->
  <div class="down_au">

    <!-- Left: description card -->
    <div class="description">
      <p>Student HUB is a modern platform designed to connect students and help them succeed. We simplify collaboration, resource sharing, and communication to make education more accessible to everyone.</p>
      <ul>
        <li><span class="check_icon">✔</span> Find help and support anytime</li>
        <li><span class="check_icon">✔</span> Connect with students from all fields</li>
        <li><span class="check_icon">✔</span> Share knowledge and grow together</li>
      </ul>
    </div>

    <!-- Right: image -->
    <div class="about_img_wrapper">
      <img src="WIN_20260506_15_53_13_Pro.jpg" alt="About Student HUB">
      <div class="emoji_badge">🎓</div>
    </div>

  </div>

</div>




<div class="services" id="services">

  <!-- Header -->
  <div class="services_header">
    <h2>Our Services</h2>
    <div class="services_underline"></div>
    <p class="services_sub">Everything you need to find help, connect with peers, and succeed in your academic journey.</p>
  </div>

  <!-- Cards -->
  <div class="services_grid">

    <div class="service_card">
      <div class="service_icon">🎯</div>
      <h3>Academic Support</h3>
      <p>Get help with your courses, exercises, and assignments from students who've been there.</p>
    </div>

    <div class="service_card">
      <div class="service_icon">💬</div>
      <h3>Student Community</h3>
      <p>Connect with peers, ask questions, and share your knowledge in a friendly environment.</p>
    </div>

    <div class="service_card">
      <div class="service_icon">📚</div>
      <h3>Resource Sharing</h3>
      <p>Access and share study materials, summaries, and tips across all fields and levels.</p>
    </div>

    <div class="service_card">
      <div class="service_icon">🔒</div>
      <h3>Secure Platform</h3>
      <p>Your data is protected and accessible only to verified students on the platform.</p>
    </div>

  </div>

</div> 


<div class="testimonials" id="testimonials">

  <!-- Background circles -->
  <div class="t_circle tc1"></div>
  <div class="t_circle tc2"></div>
  <div class="t_circle tc3"></div>
  <div class="t_circle tc4"></div>

  <!-- Header -->
  <div class="t_header">
    <h2>What our users say</h2>
    <div class="t_underline"></div>
    <p>Trusted by students from everywhere</p>
  </div>

  <!-- 3D Carousel Scene -->
  <div class="carousel3d_scene">
    <div class="carousel3d_stage" id="carousel3d">

      <div class="c3d_card" data-index="0">
        <div class="t_green_dot"></div>
        <img src="image/avatar.png" alt="avatar" class="t_avatar">
        <h3 class="t_name">Ahmed B.</h3>
        <p class="t_role">STUDENT</p>
        <div class="t_quote_top">"</div>
        <p class="t_text"><em>This platform helped me find support when I needed it the most. Highly recommended!</em></p>
        <div class="t_quote_bottom">"</div>
        <div class="t_stars">⭐⭐⭐⭐⭐</div>
      </div>

      <div class="c3d_card" data-index="1">
        <div class="t_green_dot"></div>
        <img src="image/avatar.png" alt="avatar" class="t_avatar">
        <h3 class="t_name">Sara M.</h3>
        <p class="t_role">STUDENT</p>
        <div class="t_quote_top">"</div>
        <p class="t_text"><em>Student HUB connected me with amazing peers. My grades improved a lot thanks to the community!</em></p>
        <div class="t_quote_bottom">"</div>
        <div class="t_stars">⭐⭐⭐⭐⭐</div>
      </div>

      <div class="c3d_card" data-index="2">
        <div class="t_green_dot"></div>
        <img src="image/avatar.png" alt="avatar" class="t_avatar">
        <h3 class="t_name">Youcef K.</h3>
        <p class="t_role">STUDENT</p>
        <div class="t_quote_top">"</div>
        <p class="t_text"><em>The resource sharing feature is incredible. I found summaries I couldn't find anywhere else.</em></p>
        <div class="t_quote_bottom">"</div>
        <div class="t_stars">⭐⭐⭐⭐</div>
      </div>

      <div class="c3d_card" data-index="3">
        <div class="t_green_dot"></div>
        <img src="image/avatar.png" alt="avatar" class="t_avatar">
        <h3 class="t_name">Lina R.</h3>
        <p class="t_role">STUDENT</p>
        <div class="t_quote_top">"</div>
        <p class="t_text"><em>Finally a platform that understands what students really need. Super intuitive and helpful!</em></p>
        <div class="t_quote_bottom">"</div>
        <div class="t_stars">⭐⭐⭐⭐⭐</div>
      </div>

      <div class="c3d_card" data-index="4">
        <div class="t_green_dot"></div>
        <img src="image/avatar.png" alt="avatar" class="t_avatar">
        <h3 class="t_name">Karim T.</h3>
        <p class="t_role">STUDENT</p>
        <div class="t_quote_top">"</div>
        <p class="t_text"><em>I love how easy it is to collaborate with others. Student HUB changed the way I study.</em></p>
        <div class="t_quote_bottom">"</div>
        <div class="t_stars">⭐⭐⭐⭐⭐</div>
      </div>

    </div>
  </div>

  <!-- Controls -->
  <div class="c3d_controls">
    <button class="t_arrow t_prev" id="c3d_prev">&#8249;</button>
    <div class="t_dots" id="c3d_dots"></div>
    <button class="t_arrow t_next" id="c3d_next">&#8250;</button>
  </div>

</div>




<!-- Contact Section -->
<section class="contact_us" id="contact">

  <div class="contact_bg_circle cbc1"></div>
  <div class="contact_bg_circle cbc2"></div>

  <div class="contact_header">
    <h2>Contactez-nous</h2>
    <div class="contact_underline"></div>
    <p>Nous sommes là pour vous aider et répondre à vos questions.</p>
  </div>

  <div class="contact_card">

    <!-- Left -->
    <div class="contact_left">
      <h3>Entrer en contact</h3>
      <p class="contact_left_sub">Vous pouvez nous joindre rapidement via les options ci-dessous :</p>

      <div class="contact_info_item">
        <div class="contact_icon">📞</div>
        <div>
          <h4>Appelez-nous</h4>
          <p>+213 123 456 789</p>
        </div>
      </div>

      <div class="contact_info_item">
        <div class="contact_icon">✉️</div>
        <div>
          <h4>Envoyez-nous un email</h4>
          <p>studenthub@gmail.com</p>
        </div>
      </div>

      <div class="contact_social">
        <h4>Suivez-nous</h4>
        <div class="social_icons">
          <a href="#" aria-label="Facebook" class="s_icon s_fb">f</a>
          <a href="#" aria-label="Instagram" class="s_icon s_ig">◎</a>
        </div>
      </div>
    </div>

    <!-- Right -->
    <div class="contact_right">
      <h3>Envoyez-nous un message</h3>

      <form class="contact_form">
        <div class="contact_row">
          <input type="text" placeholder="Votre nom" required>
          <input type="text" placeholder="Votre société">
        </div>

        <input type="email" placeholder="Votre email" required>
        <input type="text" placeholder="Sujet" required>
        <textarea rows="5" placeholder="Votre message" required></textarea>

        <button type="submit" class="contact_submit">Envoyer le message</button>
      </form>
    </div>

  </div>
</section>

<!-- Footer -->
<footer class="site_footer">
  <div class="footer_bg_circle fbc1"></div>
  <div class="footer_bg_circle fbc2"></div>
  <div class="footer_bg_circle fbc3"></div>

  <div class="footer_container">

    <div class="footer_col">
      <h3>Madrassati</h3>
      <p>
        Making school management simple and effective for everyone.
        Connect, communicate, and collaborate seamlessly.
      </p>
      <div class="footer_socials">
        <a href="#" aria-label="Facebook" class="f_social">●</a>
        <a href="#" aria-label="Instagram" class="f_social">●</a>
      </div>
    </div>

    <div class="footer_col">
      <h4>Liens rapides</h4>
      <ul class="footer_links">
        <li><a href="#">Accueil</a></li>
        <li><a href="#">À propos</a></li>
        <li><a href="#">Tarifs</a></li>
        <li><a href="#contact">Contact</a></li>
      </ul>
    </div>

    <div class="footer_col">
      <h4>Informations de contact</h4>
      <ul class="footer_infos">
        <li><span>✉️</span> madrassati@gmail.com</li>
        <li><span>📞</span> +213 123 456 789</li>
        <li><span>📍</span> Paris</li>
      </ul>
    </div>

  </div>
</footer>

<script>
  const cards    = document.querySelectorAll('.c3d_card');
  const stage    = document.getElementById('carousel3d');
  const dotsWrap = document.getElementById('c3d_dots');
  const total    = cards.length;
  let   current  = 0;
  let   autoTimer;

  /* ── Build dots ── */
  cards.forEach((_, i) => {
    const d = document.createElement('span');
    d.className = 't_dot' + (i === 0 ? ' active' : '');
    d.addEventListener('click', () => goTo(i));
    dotsWrap.appendChild(d);
  });

  function getDots() { return dotsWrap.querySelectorAll('.t_dot'); }

  /* ── Position logic ── */
  function applyPositions() {
    const angleStep = 360 / total;
    cards.forEach((card, i) => {
      let offset = i - current;
      /* Wrap around so we always take the shortest arc */
      if (offset > total / 2)  offset -= total;
      if (offset < -total / 2) offset += total;

      const angle   = offset * angleStep;          /* degrees from centre */
      const absOff  = Math.abs(offset);
      const isCenter = offset === 0;

      /* Scale: centre card biggest, side cards smaller */
      const scale = isCenter ? 1 : (absOff === 1 ? 0.78 : 0.58);

      /* Horizontal spread */
      const tx = offset * 200;

      /* Depth: push side cards back */
      const tz = isCenter ? 0 : -120 * absOff;

      /* Vertical drop */
      const ty = isCenter ? 0 : 20 * absOff;

      /* Opacity */
      const opacity = isCenter ? 1 : (absOff === 1 ? 0.72 : 0.38);

      /* Only show up to 1 card left/right (hide the very back ones) */
      const visible = absOff <= 2;

      card.style.transform  = 'translateX(${tx}px) translateY(${ty}px) translateZ(${tz}px) scale(${scale})';
      card.style.opacity    = visible ? opacity : 0;
      card.style.zIndex     = 10 - absOff;
      card.style.pointerEvents = isCenter ? 'auto' : 'none';
      card.classList.toggle('c3d_active', isCenter);
    });

    /* Update dots */
    getDots().forEach((d, i) => d.classList.toggle('active', i === current));
  }

  function goTo(index) {
    current = (index + total) % total;
    applyPositions();
    resetAuto();
  }

  document.getElementById('c3d_prev').addEventListener('click', () => goTo(current - 1));
  document.getElementById('c3d_next').addEventListener('click', () => goTo(current + 1));

  /* Keyboard */
  document.addEventListener('keydown', e => {
    if (e.key === 'ArrowLeft')  goTo(current - 1);
    if (e.key === 'ArrowRight') goTo(current + 1);
  });

  /* Swipe support */
  let touchStartX = 0;
  stage.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
  stage.addEventListener('touchend',   e => {
    const dx = e.changedTouches[0].clientX - touchStartX;
    if (Math.abs(dx) > 40) goTo(current + (dx < 0 ? 1 : -1));
  });

  /* Auto-play */
  function startAuto() { autoTimer = setInterval(() => goTo(current + 1), 3500); }
  function resetAuto()  { clearInterval(autoTimer); startAuto(); }

  /* Init */
  applyPositions();
  startAuto();
</script>

</div>
</body>
</html>