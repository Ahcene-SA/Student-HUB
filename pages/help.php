<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 19 Nov 1981 08:52:00 GMT");
$user_id = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;
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
  <title>Student HUB – Aide & FAQ</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="help.css?v=1" />
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
    <main class="help-layout">
      <div class="help-card">
        <h1>❓ Centre d'aide</h1>
        <p class="help-intro">Trouve rapidement des réponses à tes questions. Si tu ne trouves pas ce que tu cherches, contacte-nous !</p>

        <!-- FAQ -->
        <section class="help-section">
          <h2>📚 Questions fréquentes</h2>

          <div class="faq-list">
            <div class="faq-item">
              <button class="faq-question" onclick="this.classList.toggle('open');this.nextElementSibling.classList.toggle('open')">
                Comment créer une publication ?
                <span class="faq-icon">+</span>
              </button>
              <div class="faq-answer">
                <p>Clique sur le bouton <strong>+</strong> en bas à droite de l'écran, choisis la catégorie (Immobilier, Event, Stage, Mentoring, Bons Plans) et remplis le formulaire. Tu peux aussi créer une publication générale depuis la page d'accueil.</p>
              </div>
            </div>

            <div class="faq-item">
              <button class="faq-question" onclick="this.classList.toggle('open');this.nextElementSibling.classList.toggle('open')">
                Comment envoyer un message privé ?
                <span class="faq-icon">+</span>
              </button>
              <div class="faq-answer">
                <p>Va sur la page <a href="messages.php">Messages</a> depuis le menu <strong>Mon compte</strong>. Tu peux chercher un utilisateur dans la barre de recherche ou cliquer sur le bouton message depuis son profil.</p>
              </div>
            </div>

            <div class="faq-item">
              <button class="faq-question" onclick="this.classList.toggle('open');this.nextElementSibling.classList.toggle('open')">
                Comment modifier mon profil ?
                <span class="faq-icon">+</span>
              </button>
              <div class="faq-answer">
                <p>Rends-toi sur ton profil et clique sur le bouton <strong>✏️ Edit</strong>. Tu peux modifier tes informations, ajouter une bannière, changer ta photo de profil et ajouter des sections (Expériences, Certificats, Éducation).</p>
              </div>
            </div>

            <div class="faq-item">
              <button class="faq-question" onclick="this.classList.toggle('open');this.nextElementSibling.classList.toggle('open')">
                C'est quoi Brain Pool ?
                <span class="faq-icon">+</span>
              </button>
              <div class="faq-answer">
                <p><strong>Brain Pool</strong> est un système d'échange de compétences entre étudiants. Tu peux proposer ton aide dans une matière (1 Crédit H = 1 heure d'aide) ou demander de l'aide avant un examen avec <strong>SOS Examen</strong>.</p>
              </div>
            </div>

            <div class="faq-item">
              <button class="faq-question" onclick="this.classList.toggle('open');this.nextElementSibling.classList.toggle('open')">
                Comment suivre quelqu'un ?
                <span class="faq-icon">+</span>
              </button>
              <div class="faq-answer">
                <p>Sur le profil d'un utilisateur, clique sur le bouton <strong>FOLLOW</strong>. Tu pourras ensuite voir ses publications dans ton fil d'actualité et retrouver facilement son profil.</p>
              </div>
            </div>

            <div class="faq-item">
              <button class="faq-question" onclick="this.classList.toggle('open');this.nextElementSibling.classList.toggle('open')">
                Puis-je supprimer une publication ?
                <span class="faq-icon">+</span>
              </button>
              <div class="faq-answer">
                <p>Oui ! Sur tes propres publications, clique sur le bouton <strong>🗑️ Delete</strong> en bas à droite de la carte. Une confirmation te sera demandée.</p>
              </div>
            </div>
          </div>
        </section>

        <!-- GUIDES -->
        <section class="help-section">
          <h2>🚀 Guides rapides</h2>
          <div class="guides-grid">
            <a href="home.php" class="guide-card">
              <span class="guide-icon">🏠</span>
              <h3>Fil d'actualité</h3>
              <p>Découvre les publications de la communauté et interagis avec les posts.</p>
            </a>
            <a href="../profile/profile.php?id=<?php echo $user_id; ?>" class="guide-card">
              <span class="guide-icon">👤</span>
              <h3>Mon Profil</h3>
              <p>Personnalise ton profil, ajoute des sections et gère tes publications.</p>
            </a>
            <a href="messages.php" class="guide-card">
              <span class="guide-icon">💬</span>
              <h3>Messagerie</h3>
              <p>Discute en privé avec les autres étudiants de la plateforme.</p>
            </a>
            <a href="immobilier.php" class="guide-card">
              <span class="guide-icon">🏡</span>
              <h3>Immobilier</h3>
              <p>Trouve un logement étudiant ou propose une annonce de location.</p>
            </a>
            <a href="stage.php" class="guide-card">
              <span class="guide-icon">💼</span>
              <h3>Stage & Alternance</h3>
              <p>Consulte les offres de stage et publie tes propres opportunités.</p>
            </a>
            <a href="events.php" class="guide-card">
              <span class="guide-icon">🎉</span>
              <h3>Événements</h3>
              <p>Découvre les soirées, conférences et événements étudiants.</p>
            </a>
          </div>
        </section>

        <!-- CONTACT -->
        <section class="help-section contact-section">
          <h2>📩 Besoin d'aide supplémentaire ?</h2>
          <p>Si tu n'as pas trouvé de réponse à ta question, envoie-nous un message et on te répondra rapidement.</p>
          <div class="contact-cards">
            <div class="contact-card">
              <span class="contact-icon">📧</span>
              <h3>Email</h3>
              <p><a href="mailto:support@studenthub.com">support@studenthub.com</a></p>
            </div>
            <div class="contact-card">
              <span class="contact-icon">💬</span>
              <h3>Messagerie</h3>
              <p><a href="messages.php">Envoie-nous un message</a></p>
            </div>
            <div class="contact-card">
              <span class="contact-icon">⏰</span>
              <h3>Horaires</h3>
              <p>Disponible 7j/7<br/>Réponse sous 24h</p>
            </div>
          </div>
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
