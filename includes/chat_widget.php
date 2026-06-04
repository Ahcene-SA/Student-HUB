<!-- Floating Chat Widget -->
<div class="chat-widget" id="chat-widget">
  <button class="chat-widget-trigger" id="chat-widget-trigger" onclick="toggleChatWidget()">
    <span id="chat-widget-icon">💬</span>
    <span class="chat-widget-badge" id="chat-widget-badge">0</span>
  </button>

  <div class="chat-widget-panel" id="chat-widget-panel">
    <div class="chat-widget-header">
      <h4>Messages</h4>
      <a href="<?php echo $widget_base_path; ?>pages/messages.php" class="chat-widget-viewall">Voir tout →</a>
    </div>

    <div class="chat-widget-list" id="chat-widget-list">
      <p class="chat-widget-empty">Chargement...</p>
    </div>

    <div class="chat-widget-mini-chat" id="chat-widget-mini-chat" style="display:none;">
      <div class="chat-widget-mini-header">
        <button onclick="backToWidgetList()">←</button>
        <span id="chat-widget-mini-name">Utilisateur</span>
      </div>
      <div class="chat-widget-mini-messages" id="chat-widget-mini-messages"></div>
      <form class="chat-widget-mini-form" onsubmit="sendWidgetMessage(event)">
        <input type="hidden" id="widget-conv-id" value="" />
        <input type="hidden" id="widget-receiver-id" value="" />
        <input type="text" id="widget-input" placeholder="Écrire..." autocomplete="off" required />
        <button type="submit">➤</button>
      </form>
    </div>
  </div>
</div>

<style>
  .chat-widget { position: fixed; bottom: 24px; right: 24px; z-index: 1100; font-family: 'ROBOTO', sans-serif; }

  .chat-widget-trigger {
    width: 56px; height: 56px; border-radius: 50%;
    background: linear-gradient(135deg, #2E5961, #3e7b86);
    color: #ffffff; border: none; font-size: 1.4rem;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    box-shadow: 0 6px 20px rgba(46,89,97,0.35);
    transition: all 0.2s ease; position: relative;
  }
  .chat-widget-trigger:hover { transform: translateY(-3px) scale(1.05); }
  .chat-widget-trigger.active { transform: rotate(90deg); }

  .chat-widget-badge {
    position: absolute; top: -2px; right: -2px;
    background: #EF4444; color: #ffffff; font-size: 0.65rem;
    font-weight: 700; padding: 2px 6px; border-radius: 999px;
    min-width: 18px; text-align: center; display: none;
  }
  .chat-widget-badge.visible { display: block; }

  .chat-widget-panel {
    position: absolute; bottom: calc(100% + 14px); right: 0;
    width: 340px; max-height: 480px; background: #ffffff;
    border-radius: 16px; box-shadow: 0 16px 48px rgba(0,0,0,0.18);
    border: 1px solid #E2E6EA; overflow: hidden;
    display: flex; flex-direction: column;
    opacity: 0; transform: translateY(10px) scale(0.96);
    pointer-events: none; transform-origin: bottom right;
    transition: opacity 0.25s ease, transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
  }
  .chat-widget-panel.open {
    opacity: 1; transform: translateY(0) scale(1); pointer-events: all;
  }

  .chat-widget-header {
    padding: 14px 16px; border-bottom: 1px solid #E2E6EA;
    display: flex; justify-content: space-between; align-items: center;
    background: linear-gradient(90deg, #2E5961, #3e7b86); color: #ffffff;
    border-radius: 16px 16px 0 0;
  }
  .chat-widget-header h4 { margin: 0; font-size: 1rem; font-weight: 700; }
  .chat-widget-viewall {
    font-size: 0.8rem; color: rgba(255,255,255,0.9); text-decoration: none; font-weight: 600;
  }
  .chat-widget-viewall:hover { color: #ffffff; }

  .chat-widget-list {
    flex: 1; overflow-y: auto; padding: 8px;
  }
  .chat-widget-empty { text-align: center; color: #9ca3af; font-size: 0.85rem; padding: 20px; font-style: italic; }

  .chat-widget-conv {
    display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 10px;
    cursor: pointer; transition: background 0.15s ease;
  }
  .chat-widget-conv:hover { background: #F0F2F5; }
  .chat-widget-conv-avatar {
    width: 40px; height: 40px; border-radius: 50%; background: #2E5961; color: #fff;
    display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
    flex-shrink: 0; overflow: hidden;
  }
  .chat-widget-conv-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
  .chat-widget-conv-info { flex: 1; min-width: 0; }
  .chat-widget-conv-name {
    font-weight: 700; font-size: 0.88rem; color: #1a2332;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    display: flex; align-items: center; gap: 6px;
  }
  .chat-widget-conv-preview {
    font-size: 0.8rem; color: #9ca3af;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .chat-widget-conv-unread {
    background: linear-gradient(90deg, #2E5961, #3e7b86); color: #fff;
    font-size: 0.65rem; font-weight: 700; padding: 1px 5px; border-radius: 999px;
  }

  .chat-widget-mini-chat { display: flex; flex-direction: column; height: 100%; }
  .chat-widget-mini-header {
    padding: 10px 14px; border-bottom: 1px solid #E2E6EA;
    display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 0.9rem; color: #1a2332;
  }
  .chat-widget-mini-header button {
    background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #666;
    width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 50%;
  }
  .chat-widget-mini-header button:hover { background: #F0F2F5; }

  .chat-widget-mini-messages {
    flex: 1; overflow-y: auto; padding: 10px 12px;
    display: flex; flex-direction: column; gap: 8px; background: #F8FAFB;
    min-height: 200px; max-height: 300px;
  }
  .chat-widget-mini-form {
    padding: 10px 12px; border-top: 1px solid #E2E6EA; display: flex; gap: 8px; background: #fff;
  }
  .chat-widget-mini-form input {
    flex: 1; border: 1.5px solid #D1D5DB; border-radius: 999px; padding: 8px 14px;
    font-family: 'ROBOTO', sans-serif; font-size: 0.9rem; outline: none;
  }
  .chat-widget-mini-form input:focus { border-color: #2E5961; }
  .chat-widget-mini-form button {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(90deg, #2E5961, #3e7b86); color: #fff; border: none;
    cursor: pointer; font-size: 0.9rem; display: flex; align-items: center; justify-content: center;
  }

  .widget-bubble-me {
    align-self: flex-end; background: linear-gradient(135deg, #2E5961, #3e7b86);
    color: #fff; padding: 8px 12px; border-radius: 14px 14px 4px 14px;
    font-size: 0.85rem; max-width: 85%; word-break: break-word;
  }
  .widget-bubble-them {
    align-self: flex-start; background: #fff; color: #374151;
    padding: 8px 12px; border-radius: 14px 14px 14px 4px;
    font-size: 0.85rem; max-width: 85%; border: 1px solid #E2E6EA; word-break: break-word;
  }
  .widget-msg-time { font-size: 0.65rem; margin-top: 3px; opacity: 0.7; text-align: right; }
  .widget-bubble-them .widget-msg-time { color: #9ca3af; }
  .widget-bubble-me .widget-msg-time { color: rgba(255,255,255,0.8); }

  @media (max-width: 480px) {
    .chat-widget-panel { width: calc(100vw - 32px); right: -8px; }
  }
</style>

<script>
(function() {
  const basePath = '<?php echo $widget_base_path; ?>';
  let widgetConvId = null;
  let widgetReceiverId = null;
  let widgetLastMsgId = 0;
  let widgetPoll = null;

  window.toggleChatWidget = function() {
    const panel = document.getElementById('chat-widget-panel');
    const trigger = document.getElementById('chat-widget-trigger');
    panel.classList.toggle('open');
    trigger.classList.toggle('active');
    if (panel.classList.contains('open')) {
      loadWidgetConversations();
    }
  };

  window.loadWidgetConversations = async function() {
    try {
      const res = await fetch(basePath + 'pages/api/get_conversations.php');
      const data = await res.json();
      if (data.error) return;
      const list = document.getElementById('chat-widget-list');
      const convs = data.conversations || [];
      let totalUnread = 0;
      if (!convs.length) {
        list.innerHTML = '<p class="chat-widget-empty">Aucune conversation</p>';
      } else {
        list.innerHTML = convs.slice(0, 5).map(c => {
          totalUnread += c.unread_count;
          const avatar = c.other_user.avatar
            ? '<img src="' + basePath + 'profile/' + escapeHtml(c.other_user.avatar) + '" />'
            : '👤';
          const preview = c.last_message ? escapeHtml(c.last_message.substring(0, 30)) : 'Pas de message';
          const unread = c.unread_count > 0 ? '<span class="chat-widget-conv-unread">' + c.unread_count + '</span>' : '';
          return '<div class="chat-widget-conv" onclick="openWidgetMiniChat(' + c.conversation_id + ', ' + c.other_user.id + ', \'' + escapeHtml(c.other_user.name) + '\', \'' + escapeHtml(c.other_user.avatar || '') + '\')">' +
            '<div class="chat-widget-conv-avatar">' + avatar + '</div>' +
            '<div class="chat-widget-conv-info">' +
              '<div class="chat-widget-conv-name">' + escapeHtml(c.other_user.name) + unread + '</div>' +
              '<div class="chat-widget-conv-preview">' + preview + '</div>' +
            '</div>' +
          '</div>';
        }).join('');
      }
      const badge = document.getElementById('chat-widget-badge');
      badge.textContent = totalUnread;
      badge.classList.toggle('visible', totalUnread > 0);
    } catch (e) { console.error(e); }
  };

  window.openWidgetMiniChat = async function(convId, otherId, otherName, otherAvatar) {
    widgetConvId = convId;
    widgetReceiverId = otherId;
    widgetLastMsgId = 0;
    document.getElementById('widget-conv-id').value = convId;
    document.getElementById('widget-receiver-id').value = otherId;
    document.getElementById('chat-widget-mini-name').textContent = otherName;
    document.getElementById('chat-widget-list').style.display = 'none';
    document.getElementById('chat-widget-mini-chat').style.display = 'flex';
    await loadWidgetMessages(convId);
    if (widgetPoll) clearInterval(widgetPoll);
    widgetPoll = setInterval(() => loadWidgetMessages(convId), 4000);
  };

  window.backToWidgetList = function() {
    document.getElementById('chat-widget-mini-chat').style.display = 'none';
    document.getElementById('chat-widget-list').style.display = 'block';
    if (widgetPoll) clearInterval(widgetPoll);
    widgetConvId = null;
    widgetLastMsgId = 0;
    loadWidgetConversations();
  };

  async function loadWidgetMessages(convId) {
    try {
      const url = basePath + 'pages/api/get_messages.php?conversation_id=' + convId + (widgetLastMsgId > 0 ? '&after=' + widgetLastMsgId : '');
      const res = await fetch(url);
      const data = await res.json();
      if (data.error) return;
      const container = document.getElementById('chat-widget-mini-messages');
      const isFirst = widgetLastMsgId === 0;
      data.messages.forEach(msg => {
        if (msg.id > widgetLastMsgId) widgetLastMsgId = msg.id;
        const isMe = msg.sender_id === <?php echo isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0; ?>;
        const cls = isMe ? 'widget-bubble-me' : 'widget-bubble-them';
        const html = '<div class="' + cls + '" style="' + (isMe ? 'align-self:flex-end;' : 'align-self:flex-start;') + '">' +
          escapeHtml(msg.content) +
          '<div class="widget-msg-time">' + timeAgoShort(msg.created_at) + '</div>' +
        '</div>';
        container.insertAdjacentHTML('beforeend', html);
      });
      if (isFirst || data.messages.length > 0) container.scrollTop = container.scrollHeight;
    } catch (e) { console.error(e); }
  }

  window.sendWidgetMessage = async function(e) {
    e.preventDefault();
    const input = document.getElementById('widget-input');
    const content = input.value.trim();
    if (!content) return;
    const convId = document.getElementById('widget-conv-id').value;
    const receiverId = document.getElementById('widget-receiver-id').value;
    input.value = '';
    const formData = new FormData();
    if (convId) formData.append('conversation_id', convId);
    if (receiverId) formData.append('receiver_id', receiverId);
    formData.append('content', content);
    try {
      const res = await fetch(basePath + 'pages/api/send_message.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.error || !data.success) return;
      if (!widgetConvId && data.conversation_id) {
        widgetConvId = data.conversation_id;
        document.getElementById('widget-conv-id').value = data.conversation_id;
      }
      await loadWidgetMessages(widgetConvId || data.conversation_id);
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
    if (diff < 604800) return Math.floor(diff / 86400) + ' j';
    return new Date(datetime).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
  }

  // Initial badge load
  loadWidgetConversations();
})();
</script>
