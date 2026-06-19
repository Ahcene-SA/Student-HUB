<?php
session_start();

require __DIR__ . "/../vendor/autoload.php";




$client= new Google\Client;
$client->setClientId("669251997046-vbvcsr40nqgc56hsbvorun6honh13smd.apps.googleusercontent.com");
$client->setClientSecret("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");
$client->setRedirectUri("https://studenthub.cloud/profile/profile.php");
$client->addScope("email");
$client->addScope("profile");
$url=$client->createAuthUrl();






// Flash error message from signup_back.php
$signupError = '';
if (!empty($_SESSION['signup_error'])) {
$signupError = $_SESSION['signup_error'];
unset($_SESSION['signup_error']);
}

// Flash error message from signin_back.php
$signinError = '';
if (!empty($_SESSION['signin_error'])) {
$signinError = $_SESSION['signin_error'];
unset($_SESSION['signin_error']);
}

// Preserved form data
$form = [
'firstname' => '',
'lastname' => '',
'username' => '',
'email' => '',
'field' => '',
'university' => '',
];
if (!empty($_SESSION['signup_data'])) {
$form = array_merge($form, $_SESSION['signup_data']);
unset($_SESSION['signup_data']);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student HUB — Connexion / Inscription</title>
  <link rel="stylesheet" href="signin.css" />
</head>

<body>

  <main class="signin-page">

    <!-- ───────── LEFT SIDE ───────── -->
    <section class="signin-left">

      <canvas id="particles"></canvas>

      <div class="signin-left-content">
        <img src="../logo/Student_HUB_LOGO.png" alt="Student HUB" class="signin-logo" />
        <h1 class="signin-brand">Student HUB</h1>
        <p class="signin-tagline">
          La plateforme des étudiants — apprendre, partager, évoluer.
        </p>
      </div>
      <div class="signin-social-proof">
        <div class="avatars-row">
          <div class="mini-avatar" style="background:#2E5961;">Y.B</div>
          <div class="mini-avatar" style="background:#3e7b86;">S.M</div>
          <div class="mini-avatar" style="background:#4a8a96;">A.K</div>
          <div class="mini-avatar" style="background:#1a3a40;">L.H</div>
        </div>
        <p class="social-proof-text">Rejoignez 2 400+ étudiants déjà inscrits</p>
      </div>

    </section>

    <!-- ───────── RIGHT SIDE ───────── -->
    <section class="signin-right">

      <div class="signin-box">

        <!-- Tabs (CSS-only via :target) -->
        <nav class="signin-tabs">
          <a href="#form-signin" class="tab tab-signin">Connexion</a>
          <a href="#form-signup" class="tab tab-signup">Inscription</a>
        </nav>

        <!-- ─── SIGN IN FORM ─── -->
        <form id="form-signin" class="auth-form" method="POST" action="signin_back.php">

          <?php if ($signinError): ?>
          <div class="form-error"><?php echo htmlspecialchars($signinError); ?></div>
          <?php endif; ?>

          <h2 class="form-title">Bon retour amine!</h2>
          <p class="form-subtitle">Connectez-vous pour continuer.</p>

          <div class="form-field">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" placeholder="Adresse email" required />
          </div>

          <div class="form-field password-field">
            <label for="password">Mot de passe</label>
            <div class="input-with-icon">
              <input type="password" name="password" id="password" placeholder="Mot de passe" required />
              <input type="checkbox" id="show-pwd-1" class="show-pwd-toggle" data-target="password" />
              <label for="show-pwd-1" class="show-pwd-label" aria-label="Afficher le mot de passe">
                <svg class="icon-eye" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
                <svg class="icon-eye-off" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path
                    d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a19.79 19.79 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a19.69 19.69 0 0 1-2.16 3.19M14.12 14.12A3 3 0 0 1 9.88 9.88" />
                  <line x1="1" y1="1" x2="23" y2="23" />
                </svg>
              </label>
            </div>
          </div>

          <a href="#" class="forgot-link">Mot de passe oublié ?</a>

          <input type="submit" name="signin_submit" value="Se connecter" class="signin-btn" />

          <div class="signin-divider"><span>ou</span></div>

          <button type="button" class="google-btn" onclick="window.location.href='<?= htmlspecialchars($url) ?>';">

            <svg width="20" height="20" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
              <path fill="#FFC107"
                d="M43.6 20.5H42V20H24v8h11.3c-1.6 4.7-6.1 8-11.3 8a12 12 0 1 1 0-24c3 0 5.7 1.1 7.8 3l5.7-5.7A20 20 0 1 0 44 24c0-1.2-.1-2.4-.4-3.5z" />
              <path fill="#FF3D00"
                d="M6.3 14.7l6.6 4.8C14.6 15.5 18.9 12 24 12c3 0 5.7 1.1 7.8 3l5.7-5.7A20 20 0 0 0 6.3 14.7z" />
              <path fill="#4CAF50"
                d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2A12 12 0 0 1 12.7 28l-6.5 5A20 20 0 0 0 24 44z" />
              <path fill="#1976D2"
                d="M43.6 20.5H42V20H24v8h11.3a12 12 0 0 1-4.1 5.6l6.2 5.2C41 35.8 44 30.4 44 24c0-1.2-.1-2.4-.4-3.5z" />
            </svg>
            Continuer avec Google
          </button>

          <p class="switch-hint">
            Pas encore de compte ?
            <a href="#form-signup">Créez-en un</a>
          </p>

        </form>

        <!-- ─── SIGN UP FORM ─── -->
        <form id="form-signup" class="auth-form" method="POST" action="signup_back.php">

          <?php if ($signupError): ?>
          <div class="form-error"><?php echo htmlspecialchars($signupError); ?></div>
          <?php endif; ?>

          <h2 class="form-title">Rejoindre Student HUB</h2>
          <p class="form-subtitle">Créez votre compte en quelques secondes.</p>

          <div class="form-row">
            <div class="form-field">
              <label for="firstname">Prénom</label>
              <input type="text" name="firstname" id="firstname" placeholder="Prénom"
                value="<?php echo htmlspecialchars($form['firstname']); ?>" required />
            </div>

            <div class="form-field">
              <label for="lastname">Nom</label>
              <input type="text" name="lastname" id="lastname" placeholder="Nom"
                value="<?php echo htmlspecialchars($form['lastname']); ?>" required />
            </div>
          </div>

          <div class="form-field">
            <label for="username">Nom d'utilisateur</label>
            <input type="text" name="username" id="username" placeholder="Nom d'utilisateur"
              value="<?php echo htmlspecialchars($form['username']); ?>" required />
          </div>

          <div class="form-field">
            <label for="signup-email">Email</label>
            <input type="email" name="email" id="signup-email" placeholder="Adresse email"
              value="<?php echo htmlspecialchars($form['email']); ?>" required />
          </div>

          <div class="form-field password-field">
            <label for="signup-password">Mot de passe</label>
            <div class="input-with-icon">
              <input type="password" name="password" id="signup-password" placeholder="Mot de passe" minlength="1"
                required />
              <input type="checkbox" id="show-pwd-2" class="show-pwd-toggle" data-target="signup-password" />
              <label for="show-pwd-2" class="show-pwd-label" aria-label="Afficher le mot de passe">
                <svg class="icon-eye" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
                <svg class="icon-eye-off" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path
                    d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a19.79 19.79 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a19.69 19.69 0 0 1-2.16 3.19M14.12 14.12A3 3 0 0 1 9.88 9.88" />
                  <line x1="1" y1="1" x2="23" y2="23" />
                </svg>
              </label>
            </div>
            <div class="pwd-strength" data-strength="">
              <span class="pwd-strength-bar"><i></i></span>
              <span class="pwd-strength-label"></span>
            </div>
          </div>

          <div class="form-row">
            <div class="form-field">
              <label for="field">Filière</label>
              <input type="text" name="field" id="field" placeholder="ex : Informatique, Génie Civil..."
                value="<?php echo htmlspecialchars($form['field']); ?>" required />
            </div>

            <div class="form-field">
              <label for="university">École / Université</label>
              <input type="text" name="university" id="university" placeholder="Votre école ou université"
                value="<?php echo htmlspecialchars($form['university']); ?>" required />
            </div>
          </div>

          <input type="submit" name="signup_submit" value="Créer mon compte" class="signin-btn" />

          <p class="terms-text">
            En créant un compte, vous acceptez nos
            <a href="#">Conditions d'utilisation</a>
          </p>

          <p class="switch-hint">
            Déjà un compte ?
            <a href="#form-signin">Connectez-vous</a>
          </p>

        </form>

      </div>

    </section>

  </main>

  <script>
  /* ───── Particle animation (left side only) ───── */
  const canvas = document.getElementById('particles');
  const ctx = canvas.getContext('2d');

  function resize() {
    const parent = canvas.parentElement;
    canvas.width = parent.offsetWidth;
    canvas.height = parent.offsetHeight;
  }
  resize();
  window.addEventListener('resize', resize);

  const PARTICLE_COUNT = 60;
  const MAX_DISTANCE = 130;
  const COLOR = 'rgba(255, 255, 255, ';
  const particles = [];

  class Particle {
    constructor() {
      this.reset();
    }
    reset() {
      this.x = Math.random() * canvas.width;
      this.y = Math.random() * canvas.height;
      this.vx = (Math.random() - 0.5) * 0.8;
      this.vy = (Math.random() - 0.5) * 0.8;
      this.radius = Math.random() * 2.5 + 1;
      this.opacity = Math.random() * 0.4 + 0.2;
    }
    update() {
      this.x += this.vx;
      this.y += this.vy;
      if (this.x < 0 || this.x > canvas.width) this.vx *= -1;
      if (this.y < 0 || this.y > canvas.height) this.vy *= -1;
    }
    draw() {
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
      ctx.fillStyle = COLOR + this.opacity + ')';
      ctx.fill();
    }
  }
  for (let i = 0; i < PARTICLE_COUNT; i++) particles.push(new Particle());

  function drawLines() {
    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const dx = particles[i].x - particles[j].x;
        const dy = particles[i].y - particles[j].y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < MAX_DISTANCE) {
          const opacity = (1 - dist / MAX_DISTANCE) * 0.3;
          ctx.beginPath();
          ctx.moveTo(particles[i].x, particles[i].y);
          ctx.lineTo(particles[j].x, particles[j].y);
          ctx.strokeStyle = COLOR + opacity + ')';
          ctx.lineWidth = 0.8;
          ctx.stroke();
        }
      }
    }
  }

  function animate() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    particles.forEach(p => {
      p.update();
      p.draw();
    });
    drawLines();
    requestAnimationFrame(animate);
  }
  animate();

  /* ───── Show / hide password toggle ───── */
  document.querySelectorAll('.show-pwd-toggle').forEach(cb => {
    cb.addEventListener('change', () => {
      const target = document.getElementById(cb.dataset.target);
      if (!target) return;
      target.type = cb.checked ? 'text' : 'password';
    });
  });

  /* ───── Password strength (3-tier requires reading value) ───── */
  const pwd = document.getElementById('signup-password');
  const strengthBox = document.querySelector('.pwd-strength');
  const strengthLabel = strengthBox.querySelector('.pwd-strength-label');
  pwd.addEventListener('input', () => {
    const len = pwd.value.length;
    let level = '';
    let txt = '';
    if (len === 0) {
      level = '';
      txt = '';
    } else if (len < 6) {
      level = 'weak';
      txt = 'Faible';
    } else if (len <= 10) {
      level = 'medium';
      txt = 'Moyen';
    } else {
      level = 'strong';
      txt = 'Fort';
    }
    strengthBox.dataset.strength = level;
    strengthLabel.textContent = txt;
  });
  </script>

</body>

</html>