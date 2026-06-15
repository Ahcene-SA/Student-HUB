<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 19 Nov 1981 08:52:00 GMT");
$conn = new mysqli("127.0.0.1", "root", "root", "studenthub", 8889);

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

// Auto-create tables
$conn->query("
    CREATE TABLE IF NOT EXISTS conversations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user1_id INT NOT NULL,
        user2_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_pair (user1_id, user2_id),
        INDEX idx_user1 (user1_id),
        INDEX idx_user2 (user2_id),
        INDEX idx_updated (updated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$conn->query("
    CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT NOT NULL,
        sender_id INT NOT NULL,
        content TEXT NOT NULL,
        is_read TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_conversation (conversation_id),
        INDEX idx_sender (sender_id),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    if ($diff < 60) return 'À l\'instant';
    if ($diff < 3600) return floor($diff / 60) . ' min';
    if ($diff < 86400) return floor($diff / 3600) . ' h';
    if ($diff < 604800) return floor($diff / 86400) . ' j';
    return date('d M', $time);
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
  <title>Student HUB - Messages</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="./messages.css?v=1" />
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
            <a href="settings.php" class="user-menu-item"><span class="user-menu-icon">⚙️</span> Settings</a>
            <a href="help.php" class="user-menu-item"><span class="user-menu-icon">❓</span> Help</a>
            <div class="user-menu-divider"></div>
            <a href="../logout.php" class="user-menu-item user-menu-logout"><span class="user-menu-icon">🚪</span> Log out</a>
          </div>
        </div>
      </nav>
    </header>

    <!-- MESSENGER LAYOUT -->
    <section class="messenger_layout">

      <!-- CONVERSATIONS SIDEBAR -->
      <aside class="conv_sidebar" id="conv-sidebar">
        <div class="conv_header">
          <h2>💬 Messages</h2>
          <input type="text" id="search-users" placeholder="Rechercher un utilisateur..." class="search_input" />
          <div class="search_results" id="search-results"></div>
        </div>
        <div class="conv_list" id="conv-list">
          <p class="conv_empty">Chargement...</p>
        </div>
      </aside>

      <!-- CHAT AREA -->
      <main class="chat_area" id="chat-area">
        <div class="chat_placeholder" id="chat-placeholder">
          <div class="chat_placeholder_inner">
            <span style="font-size:3rem;">💬</span>
            <h3>Sélectionnez une conversation</h3>
            <p>Choisissez un utilisateur dans la liste pour commencer à discuter.</p>
          </div>
        </div>

        <div class="chat_active" id="chat-active" style="display:none;">
          <div class="chat_header" id="chat-header">
            <div class="chat_header_user">
              <div class="avatar sm">👤</div>
              <span>Utilisateur</span>
            </div>
          </div>

          <div class="chat_messages" id="chat-messages"></div>

          <div class="chat_form_wrap">
            <form id="chat-form" onsubmit="sendMessage(event)">
              <input type="hidden" id="chat-conversation-id" value="" />
              <input type="hidden" id="chat-receiver-id" value="" />
              <div class="chat_input_row">
                <input type="text" id="chat-input" placeholder="Écrire un message..." autocomplete="off" required />
                <button type="submit" class="chat_send_btn">➤</button>
              </div>
            </form>
          </div>
        </div>
      </main>

    </section>
  </div>

  <script>
    let currentConversationId = null;
    let currentReceiverId = null;
    let lastMessageId = 0;
    let pollingInterval = null;
    let conversationsData = [];

    /* ── Load conversations ── */
    async function loadConversations() {
      try {
        const res = await fetch('api/get_conversations.php');
        const data = await res.json();
        if (data.error) return;
        conversationsData = data.conversations || [];
        renderConversations();
      } catch (e) { console.error(e); }
    }

    function renderConversations() {
      const list = document.getElementById('conv-list');
      if (!conversationsData.length) {
        list.innerHTML = '<p class="conv_empty">Aucune conversation.</p>';
        return;
      }
      list.innerHTML = conversationsData.map(c => {
        const isActive = currentConversationId === c.conversation_id ? 'active' : '';
        const avatarHtml = c.other_user.avatar
          ? '<img src="../profile/' + escapeHtml(c.other_user.avatar) + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;" />'
          : '👤';
        const unreadBadge = c.unread_count > 0 ? '<span class="conv_unread">' + c.unread_count + '</span>' : '';
        const isMe = c.last_sender_id === <?php echo $user_id; ?> ? 'Vous: ' : '';
        const preview = c.last_message ? escapeHtml(isMe + c.last_message.substring(0, 40)) : 'Pas encore de message';
        const time = timeAgoShort(c.last_message_at || c.updated_at);
        return `
          <div class="conv_item ${isActive}" onclick="openConversation(${c.conversation_id}, ${c.other_user.id}, '${escapeHtml(c.other_user.name)}', '${escapeHtml(c.other_user.avatar || '')}')">
            <div class="conv_avatar">${avatarHtml}</div>
            <div class="conv_info">
              <div class="conv_top">
                <span class="conv_name">${escapeHtml(c.other_user.name)}</span>
                <span class="conv_time">${time}</span>
              </div>
              <div class="conv_preview">${preview}${unreadBadge}</div>
            </div>
          </div>
        `;
      }).join('');
    }

    /* ── Open conversation ── */
    async function openConversation(convId, otherId, otherName, otherAvatar) {
      currentConversationId = convId;
      currentReceiverId = otherId;
      lastMessageId = 0;
      document.getElementById('chat-conversation-id').value = convId;
      document.getElementById('chat-receiver-id').value = otherId;

      document.getElementById('chat-placeholder').style.display = 'none';
      document.getElementById('chat-active').style.display = 'flex';

      // Update header
      const avatarHtml = otherAvatar
        ? '<img src="../profile/' + escapeHtml(otherAvatar) + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;" />'
        : '👤';
      document.getElementById('chat-header').innerHTML = `
        <div class="chat_header_user">
          <div class="avatar sm">${avatarHtml}</div>
          <a href="../profile/profile.php?id=${otherId}" style="text-decoration:none;color:inherit;"><span>${escapeHtml(otherName)}</span></a>
        </div>
      `;

      // Update active state in sidebar
      renderConversations();

      // Load messages
      await loadMessages(convId);

      // Start polling
      if (pollingInterval) clearInterval(pollingInterval);
      pollingInterval = setInterval(() => loadMessages(convId), 3000);
    }

    /* ── Load messages ── */
    async function loadMessages(convId) {
      try {
        const url = 'api/get_messages.php?conversation_id=' + convId + (lastMessageId > 0 ? '&after=' + lastMessageId : '');
        const res = await fetch(url);
        const data = await res.json();
        if (data.error) return;

        const container = document.getElementById('chat-messages');
        const isFirstLoad = lastMessageId === 0;

        data.messages.forEach(msg => {
          if (msg.id > lastMessageId) lastMessageId = msg.id;
          const isMe = msg.sender_id === <?php echo $user_id; ?>;
          const bubbleClass = isMe ? 'chat_bubble_me' : 'chat_bubble_them';
          const time = timeAgoShort(msg.created_at);
          const html = `
            <div class="chat_message ${isMe ? 'me' : 'them'}">
              ${isMe ? '' : '<div class="chat_msg_avatar">' + (msg.author_avatar ? '<img src="../profile/' + escapeHtml(msg.author_avatar) + '" />' : '👤') + '</div>'}
              <div class="${bubbleClass}">
                <div class="chat_msg_text">${escapeHtml(msg.content)}</div>
                <div class="chat_msg_time">${time} ${isMe ? (msg.is_read ? '✓✓' : '✓') : ''}</div>
              </div>
            </div>
          `;
          container.insertAdjacentHTML('beforeend', html);
        });

        if (isFirstLoad || data.messages.length > 0) {
          container.scrollTop = container.scrollHeight;
        }
      } catch (e) { console.error(e); }
    }

    /* ── Send message ── */
    async function sendMessage(e) {
      e.preventDefault();
      const input = document.getElementById('chat-input');
      const content = input.value.trim();
      if (!content) return;

      const convId = document.getElementById('chat-conversation-id').value;
      const receiverId = document.getElementById('chat-receiver-id').value;

      const formData = new FormData();
      if (convId) formData.append('conversation_id', convId);
      if (receiverId) formData.append('receiver_id', receiverId);
      formData.append('content', content);

      input.value = '';

      try {
        const res = await fetch('api/send_message.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.error || !data.success) return;

        if (!currentConversationId && data.conversation_id) {
          currentConversationId = data.conversation_id;
          document.getElementById('chat-conversation-id').value = data.conversation_id;
          await loadConversations();
        }
        await loadMessages(currentConversationId);
      } catch (e) { console.error(e); }
    }

    /* ── Search users ── */
    let searchTimeout = null;
    document.getElementById('search-users').addEventListener('input', function() {
      clearTimeout(searchTimeout);
      const q = this.value.trim();
      const results = document.getElementById('search-results');
      if (!q) { results.style.display = 'none'; return; }
      searchTimeout = setTimeout(() => searchUsers(q), 300);
    });

    async function searchUsers(q) {
      try {
        const res = await fetch('api/search_users.php?q=' + encodeURIComponent(q));
        const data = await res.json();
        const results = document.getElementById('search-results');
        if (!data.users || !data.users.length) {
          results.innerHTML = '<p style="padding:8px;color:#888;font-size:0.85rem;">Aucun résultat</p>';
          results.style.display = 'block';
          return;
        }
        results.innerHTML = data.users.map(u => {
          const avatar = u.avatar ? '<img src="../profile/' + escapeHtml(u.avatar) + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;" />' : '👤';
          return `
            <div class="search_result_item" onclick="startNewConversation(${u.id}, '${escapeHtml(u.name)}', '${escapeHtml(u.avatar || '')}')">
              <div class="conv_avatar">${avatar}</div>
              <span>${escapeHtml(u.name)}</span>
            </div>
          `;
        }).join('');
        results.style.display = 'block';
      } catch (e) { console.error(e); }
    }

    async function startNewConversation(otherId, otherName, otherAvatar) {
      document.getElementById('search-users').value = '';
      document.getElementById('search-results').style.display = 'none';
      // Try to find existing conversation
      await loadConversations();
      const existing = conversationsData.find(c => c.other_user.id === otherId);
      if (existing) {
        openConversation(existing.conversation_id, otherId, otherName, otherAvatar);
      } else {
        // Open empty chat with receiver_id set
        currentConversationId = null;
        currentReceiverId = otherId;
        document.getElementById('chat-conversation-id').value = '';
        document.getElementById('chat-receiver-id').value = otherId;
        document.getElementById('chat-placeholder').style.display = 'none';
        document.getElementById('chat-active').style.display = 'flex';
        const avatarHtml = otherAvatar
          ? '<img src="../profile/' + escapeHtml(otherAvatar) + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;" />'
          : '👤';
        document.getElementById('chat-header').innerHTML = `
          <div class="chat_header_user">
            <div class="avatar sm">${avatarHtml}</div>
            <a href="../profile/profile.php?id=${otherId}" style="text-decoration:none;color:inherit;"><span>${escapeHtml(otherName)}</span></a>
          </div>
        `;
        document.getElementById('chat-messages').innerHTML = '';
        if (pollingInterval) clearInterval(pollingInterval);
      }
    }

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
      if (diff < 604800) return Math.floor(diff / 86400) + ' j';
      return new Date(datetime).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
    }

    // Click outside search results to close
    document.addEventListener('click', function(e) {
      if (!e.target.closest('.conv_header')) {
        document.getElementById('search-results').style.display = 'none';
      }
    });

    // Initial load
    loadConversations();

    /* Navbar dropdown fix */
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
