-- Create posts table for Student HUB
-- General posts appear on home feed and user's profile
-- Category posts (immobilier, stage, events, bonplan, mentoring) appear only on their page

CREATE TABLE IF NOT EXISTS posts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    category    VARCHAR(30) NOT NULL DEFAULT 'general',
    title       VARCHAR(255),
    content     TEXT NOT NULL,
    image       VARCHAR(255),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user     (user_id),
    INDEX idx_category (category),
    INDEX idx_created  (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories:
-- 'general'    → home feed + profile
-- 'immobilier' → immobilier.php only
-- 'stage'      → stage.php only
-- 'events'     → events.php only
-- 'bonplan'    → bonplan.php only
-- 'mentoring'  → mentoring.php only
