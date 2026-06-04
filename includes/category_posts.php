<?php
/**
 * Helper: fetch and render category-specific posts.
 * Include this file on any category page, then call renderCategoryPosts('category_name').
 */

function renderCategoryPosts(string $category): void {
    $conn = new mysqli("127.0.0.1", "root", "", "devweb", 3306);
    if ($conn->connect_error) return;

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

    $stmt = $conn->prepare("
        SELECT p.*, u.prenom, u.nom, u.username, u.avatar
        FROM posts p
        JOIN user u ON u.id_user = p.user_id
        WHERE p.category = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();

    function catTimeAgo($datetime) {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;
        if ($diff < 60) return "À l'instant";
        if ($diff < 3600) return floor($diff / 60) . ' min';
        if ($diff < 86400) return floor($diff / 3600) . ' h';
        if ($diff < 604800) return floor($diff / 86400) . ' j';
        return date('d M Y', $time);
    }
    ?>
    <section class="category-posts" style="max-width:900px;margin:2rem auto;padding:0 1rem;">
      <h2 style="font-size:1.4rem;font-weight:700;color:#1a2332;margin-bottom:1rem;">
        Publications récentes
      </h2>
      <?php if ($result->num_rows === 0): ?>
        <div style="background:#fff;border-radius:16px;padding:2rem;text-align:center;color:#666;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
          <p>Aucune publication dans cette catégorie pour le moment.</p>
        </div>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:1rem;">
          <?php while ($post = $result->fetch_assoc()): ?>
            <article style="background:#fff;border-radius:16px;padding:1.25rem;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
              <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem;">
                <div style="width:40px;height:40px;border-radius:50%;overflow:hidden;background:#f0f0f0;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">
                  <?php echo !empty($post['avatar']) ? '<img src="../profile/' . htmlspecialchars($post['avatar']) . '" style="width:100%;height:100%;object-fit:cover;" />' : '👤'; ?>
                </div>
                <div>
                  <p style="margin:0;font-weight:700;color:#1a2332;font-size:0.95rem;">
                    <?php echo htmlspecialchars($post['prenom'] . ' ' . $post['nom']); ?>
                  </p>
                  <p style="margin:0;font-size:0.8rem;color:#888;">
                    @<?php echo htmlspecialchars($post['username']); ?> · <?php echo catTimeAgo($post['created_at']); ?>
                  </p>
                </div>
              </div>
              <?php if (!empty($post['title'])): ?>
                <h3 style="margin:0 0 0.5rem;font-size:1.1rem;color:#1a2332;"><?php echo htmlspecialchars($post['title']); ?></h3>
              <?php endif; ?>
              <p style="margin:0 0 0.75rem;color:#444;line-height:1.5;"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
              <?php if (!empty($post['image'])): ?>
                <img src="../profile/<?php echo htmlspecialchars($post['image']); ?>" alt="" style="width:100%;max-height:400px;object-fit:cover;border-radius:12px;margin-bottom:0.75rem;">
              <?php endif; ?>
              <div style="display:flex;gap:1rem;font-size:0.85rem;color:#666;">
                <span>👍 Like</span>
                <span>💬 Comment</span>
                <span>🔗 Share</span>
              </div>
            </article>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </section>
    <?php
    $stmt->close();
    $conn->close();
}
?>
