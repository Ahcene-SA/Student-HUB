-- Active: 1778770129275@@127.0.0.1@3306@devweb
CREATE TABLE user(
   id_user INT PRIMARY KEY AUTO_INCREMENT,  
    prenom VARCHAR(100) NOT NULL ,
   nom VARCHAR(100) NOT NULL,
   username VARCHAR(100) NOT NULL UNIQUE ,
   email VARCHAR(100) NOT NULL UNIQUE,
   mdp VARCHAR(100) NOT NULL UNIQUE,
   filliere VARCHAR(100) NOT NULL,
   school VARCHAR(100) NOT NULL
);

-- Add columns to user table for profile features
ALTER TABLE user ADD COLUMN phone      VARCHAR(20);
ALTER TABLE user ADD COLUMN promotion  VARCHAR(20);
ALTER TABLE user ADD COLUMN specialite VARCHAR(100);
ALTER TABLE user ADD COLUMN niveau     VARCHAR(50);
ALTER TABLE user ADD COLUMN universite VARCHAR(100);
ALTER TABLE user ADD COLUMN instagram  VARCHAR(255);
ALTER TABLE user ADD COLUMN linkedin   VARCHAR(255);
ALTER TABLE user ADD COLUMN facebook   VARCHAR(255);
ALTER TABLE user ADD COLUMN avatar     VARCHAR(255);
ALTER TABLE user ADD COLUMN banner     VARCHAR(255);

-- New table for right column sections
CREATE TABLE IF NOT EXISTS user_sections (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    type        ENUM('experience','certificate','education') NOT NULL,
    content     VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id_user) ON DELETE CASCADE
);