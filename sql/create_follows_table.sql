-- SQL to create the follows table for Student HUB
-- Run this in phpMyAdmin or MySQL console

CREATE TABLE IF NOT EXISTS follows (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    follower_id  INT NOT NULL,
    following_id INT NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_follow (follower_id, following_id),
    FOREIGN KEY (follower_id)  REFERENCES user(id_user) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES user(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index for fast "who is this user following?" lookups
CREATE INDEX idx_follower  ON follows(follower_id);

-- Index for fast "who follows this user?" lookups
CREATE INDEX idx_following ON follows(following_id);

-- Sample queries you can test after inserting data:
-- Count followers of user #5:
-- SELECT COUNT(*) FROM follows WHERE following_id = 5;

-- Count how many users #5 follows:
-- SELECT COUNT(*) FROM follows WHERE follower_id = 5;

-- Get list of users that #5 follows:
-- SELECT u.id_user, u.prenom, u.nom, u.username
-- FROM follows f
-- JOIN user u ON u.id_user = f.following_id
-- WHERE f.follower_id = 5;
