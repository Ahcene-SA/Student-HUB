# StudentHub

**StudentHub** is a full-stack social network and marketplace platform built for students. It combines a Facebook-style social feed with specialized category boards for real estate, internships, events, classifieds, and peer-to-peer mentoring. The project is written in procedural PHP with a hybrid MySQLi/PDO database layer, vanilla JavaScript, and custom CSS.

---

## Purpose

The goal of StudentHub is to give students a single place to:
- Share posts and interact with likes and comments.
- Follow each other and build a profile with experiences, certificates, and education.
- Browse and publish listings for housing (Immobilier), internships (Stage), events (Events), deals (Bons Plans), and mentoring.
- Chat privately in real time with other students.
- Log in with Google OAuth or a local account.

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Language | PHP 8.x |
| Database | MySQL (InnoDB, utf8mb4) |
| DB Drivers | MySQLi (category pages & messaging API) + PDO (profile & actions) |
| Frontend | HTML5, vanilla CSS3, vanilla JavaScript |
| CSS Framework | Bootstrap 5.3.8 (landing page only) |
| Fonts | Google Fonts (Inter, Bebas Neue, Playfair Display, Syne, Nunito, Caveat, JetBrains Mono) |
| Icons | SVG / Emoji |
| Auth | PHP sessions + `password_hash` / Google OAuth 2.0 |

---

## Features

### Authentication & Profiles
- **Registration / Login** with email and password (minimum 8 characters, hashed with `password_hash`).
- **Google OAuth** single sign-on via `includes/google_auth.php`.
- **Profile page** with banner and avatar upload, editable fields (filière, université, niveau, social links), and right-column sections (Experience, Certificates, Education).
- **Follow / Unfollow** system with follower/following counts.

### Social Feed
- **Home feed** (`pages/home.php`) with a composer card for general posts.
- **Posts** support title, content, and an optional image.
- **Like / Unlike** posts with real-time counts.
- **Comments** on posts with nested like support.
- **Post detail modal** and **comment overlay modal** for focused reading.

### Category Boards
Each board has its own page with a hero slider, search bar, and filter board:
- **Immobilier** — filter by price, type (studio/appartement/chambre), bedrooms, and furnished status.
- **Stage** — filter by domain, duration, city, and type (stage/alternance).
- **Events** — filter by city, date, category, and free/paid status.
- **Bons Plans** — filter by category (books, electronics, furniture, clothes, courses, misc), price, condition, and city.
- **Mentoring** — filter by subject, level, availability, language, and price per hour.

All category posts are created from a **floating post bubble** on the profile page, which opens themed modals for each category.

### Messaging
- **Full-page messenger** (`pages/messages.php`) with a conversation sidebar and chat area.
- **Real-time polling** every 3 seconds for new messages.
- **Unread badges** and read receipts (✓ / ✓✓).
- **Message deletion** (soft delete with "Message supprimé" placeholder).
- **Floating chat widget** (`includes/chat_widget.php`) available across pages for quick access.
- **User search** to start new conversations.

### Extra Pages
- **Settings** (`pages/settings.php`) — update personal info and change password.
- **Help / FAQ** (`pages/help.php`) — accordion-style FAQ and quick guides.

---

## Frontend

### Page Structure
```
index.php                 → redirects to mainpage/main.php
mainpage/main.php         → Public landing page (Bootstrap 5.3.8)
persoinfo/signin.php      → Combined login / register page
profile/profile.php       → Profile & post creation hub
pages/home.php            → Social feed
pages/immobilier.php      → Real estate board
pages/stage.php           → Internship board
pages/events.php          → Events board
pages/bonplan.php         → Classifieds board
pages/mentoring.php       → Mentoring board
pages/messages.php        → Private messenger
pages/settings.php        → Account settings
pages/help.php            → Help center
```

### Styling
- **Landing page** uses Bootstrap 5.3.8 for its navbar, hero sections, and contact form.
- **Internal pages** use custom CSS files (`profile.css`, `home.css`, `messages.css`, `stage.css`, `events.css`, `bonplan.css`, `immobilier.css`, `mentoring.css`, `settings.css`, `help.css`).
- **Shared components**: a consistent top navbar with a user dropdown menu appears on every internal page.
- **Animations**: CSS `IntersectionObserver` reveal animations, hero image sliders with dot navigation, and modal transitions.

### JavaScript
- **AJAX** via `fetch()` for likes, comments, messages, and conversations.
- **Polling** for messenger (`setInterval` every 3s).
- **Modals** for post details, comments, and the floating post bubbles.
- **Client-side session guard** on every page: fetches `check_session.php` and redirects to login if the session is missing.

---

## Backend

### Architecture
The backend is procedural PHP. There is no framework; routing is done by direct file access.

### Key Files

| File | Role |
|------|------|
| `includes/db_config.php` | Returns `get_db_connection()` (MySQLi) and `get_pdo_connection()` (PDO). Production defaults point to a Dokploy MySQL service. |
| `profile/db.php` | Thin wrapper that loads `db_config.php` and exposes `$pdo`. |
| `check_session.php` | Returns JSON `{"logged_in": true/false}` based on `$_SESSION['id_user']`. |
| `logout.php` | Clears the session and redirects to the login page. |

### Authentication Flow
1. `signin_back.php` validates credentials with prepared statements and `password_verify()`.
2. On success, `$_SESSION['id_user']` is set.
3. `signup_back.php` hashes passwords with `password_hash()`, inserts the user, and handles duplicate entry errors via `duplicate_error_helper.php`.
4. `google_auth.php` handles the OAuth 2.0 code exchange, creates/finds the user, and starts a session.

### Profile Actions
| File | Action |
|------|--------|
| `profile/upload_photo.php` | Uploads avatar/banner images to `uploads/avatars/` and `uploads/banners/`. |
| `profile/update_banner.php` | Updates profile text fields (filière, université, social links). |
| `profile/update_info.php` | Updates phone, promotion, spécialité, and niveau. |
| `profile/add_section.php` | Adds an Experience / Certificate / Education entry. |
| `profile/delete_section.php` | Removes a section entry. |
| `profile/follow.php` | Creates a follow record. |
| `profile/unfollow.php` | Removes a follow record. |
| `profile/create_post.php` | Inserts a post with category-specific fields and optional image upload to `uploads/posts/`. |
| `profile/delete_post.php` | Deletes the user's own post and its image file. |

### API Endpoints (`pages/api/`)
| Endpoint | Method | Description |
|----------|--------|-------------|
| `like_post.php` | POST | Toggle like on a post; returns `{liked, count}`. |
| `like_comment.php` | POST | Toggle like on a comment; returns `{liked, count}`. |
| `add_comment.php` | POST | Add a comment to a post; returns the new comment object. |
| `get_comments.php` | GET | Fetch comments + post like status for a given `post_id`. |
| `get_conversations.php` | GET | List all conversations for the logged-in user with last message and unread count. |
| `get_messages.php` | GET | Fetch messages for a conversation; supports `after` cursor for polling. |
| `send_message.php` | POST | Send a message; creates a conversation if needed. |
| `delete_message.php` | POST | Soft-delete a message (sets `is_deleted = 1`). |
| `search_users.php` | GET | Search users by name/username for starting new chats. |
| `mark_read.php` | POST | Mark all messages in a conversation as read. |
| `get_unread_count.php` | GET | Returns total unread messages across all conversations. |

---

## Database

### Schema Overview
The database uses **InnoDB** with **utf8mb4** collation. Tables are auto-created by the application if they do not exist.

#### `user`
Stores registered students.
```
id_user      INT PK AUTO_INCREMENT
prenom       VARCHAR
nom          VARCHAR
username     VARCHAR UNIQUE
email        VARCHAR UNIQUE
mdp          VARCHAR (password_hash)
filliere     VARCHAR
school       VARCHAR
phone        VARCHAR
promotion    VARCHAR
specialite   VARCHAR
niveau       VARCHAR
universite   VARCHAR
instagram    VARCHAR
linkedin     VARCHAR
facebook     VARCHAR
avatar       VARCHAR (relative path)
banner       VARCHAR (relative path)
```

#### `posts`
Unified table for all posts (general + category listings).
```
id                INT PK AUTO_INCREMENT
user_id           INT
 category         VARCHAR(30)   -- general | immobilier | stage | events | bonplan | mentoring
 title            VARCHAR(255)
 content          TEXT
 image            VARCHAR(255)
 price            VARCHAR(50)
 location         VARCHAR(255)
 event_date       VARCHAR(50)
 company          VARCHAR(255)
 chambres         INT
 meuble           VARCHAR(20)
 property_type    VARCHAR(50)
 type_event       VARCHAR(50)
 tarif            VARCHAR(50)
 is_free          VARCHAR(10)
 domaine          VARCHAR(100)
 duree            VARCHAR(50)
 type_stage       VARCHAR(30)
 niveau_etude     VARCHAR(50)
 matiere          VARCHAR(100)
 niveau_mentoring VARCHAR(50)
 langue           VARCHAR(50)
 disponibilite    VARCHAR(50)
 is_mentor        VARCHAR(10)
 prix_mentoring   VARCHAR(50)
 produit          VARCHAR(255)
 etat             VARCHAR(50)
 bonplan_category VARCHAR(50)
 created_at       TIMESTAMP
 updated_at       TIMESTAMP
```

#### `follows`
Follower relationships.
```
id            INT PK AUTO_INCREMENT
follower_id   INT
following_id  INT
 created_at   TIMESTAMP
```

#### `user_sections`
Profile resume sections.
```
id           INT PK AUTO_INCREMENT
user_id      INT
type         VARCHAR(30)   -- experience | certificate | education
title        VARCHAR(255)
description  VARCHAR(150)
date_value   VARCHAR(50)
created_at   TIMESTAMP
```

#### `post_likes`
```
id         INT PK AUTO_INCREMENT
post_id    INT
user_id    INT
created_at TIMESTAMP
UNIQUE(post_id, user_id)
```

#### `comments`
```
id         INT PK AUTO_INCREMENT
post_id    INT
user_id    INT
content    TEXT
created_at TIMESTAMP
```

#### `comment_likes`
```
id          INT PK AUTO_INCREMENT
comment_id  INT
user_id     INT
created_at  TIMESTAMP
UNIQUE(comment_id, user_id)
```

#### `conversations`
```
id         INT PK AUTO_INCREMENT
user1_id   INT
user2_id   INT
created_at TIMESTAMP
updated_at TIMESTAMP
UNIQUE(user1_id, user2_id)
```

#### `messages`
```
id               INT PK AUTO_INCREMENT
conversation_id  INT
sender_id        INT
content          TEXT
is_read          TINYINT DEFAULT 0
is_deleted       TINYINT DEFAULT 0
created_at       TIMESTAMP
```

---

## Installation

1. **Clone / copy** the project into your web root (e.g., `C:\wamp64\www\projet web dev` or `/var/www/html/studenthub`).
2. **Create a MySQL database** (e.g., `StudenthubDB`).
3. **Import the base schema** from `base de donne/BD.sql`.
4. **Configure the database connection** in `includes/db_config.php`:
   ```php
   $host = 'localhost';
   $user = 'your_db_user';
   $pass = 'your_db_password';
   $db   = 'StudenthubDB';
   ```
5. **Ensure write permissions** for the web server on:
   - `profile/uploads/avatars/`
   - `profile/uploads/banners/`
   - `profile/uploads/posts/`
6. **(Optional) Google OAuth**:
   - Set environment variables `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI`,
   - Or edit the fallback values in `includes/google_auth.php`.
7. **Open the site** in a browser at `http://localhost/projet%20web%20dev/`.

---

## File Structure

```
projet web dev/
├── index.php
├── check_session.php
├── logout.php
├── includes/
│   ├── db_config.php
│   ├── google_auth.php
│   ├── chat_widget.php
│   ├── category_posts.php
│   └── duplicate_error_helper.php
├── base de donne/
│   └── BD.sql
├── mainpage/
│   └── main.php
├── persoinfo/
│   ├── signin.php
│   ├── signin_back.php
│   └── signup_back.php
├── profile/
│   ├── profile.php
│   ├── profile.css
│   ├── db.php
│   ├── upload_photo.php
│   ├── update_banner.php
│   ├── update_info.php
│   ├── add_section.php
│   ├── delete_section.php
│   ├── create_post.php
│   └── delete_post.php
│   └── uploads/
│       ├── avatars/
│       ├── banners/
│       └── posts/
├── pages/
│   ├── home.php
│   ├── home.css
│   ├── immobilier.php
│   ├── immobilier.css
│   ├── stage.php
│   ├── stage.css
│   ├── events.php
│   ├── events.css
│   ├── bonplan.php
│   ├── bonplan.css
│   ├── mentoring.php
│   ├── mentoring.css
│   ├── messages.php
│   ├── messages.css
│   ├── settings.php
│   ├── settings.css
│   ├── help.php
│   └── help.css
│   └── api/
│       ├── like_post.php
│       ├── like_comment.php
│       ├── add_comment.php
│       ├── get_comments.php
│       ├── get_conversations.php
│       ├── get_messages.php
│       ├── send_message.php
│       ├── delete_message.php
│       ├── search_users.php
│       ├── mark_read.php
│       └── get_unread_count.php
├── logo/
│   └── Student_HUB_LOGO.png
└── img/
    ├── event/
    ├── imo/
    ├── bp/
    ├── stage/
    └── mentor/
```

---

## Security Notes

- Prepared statements are used for all database queries to prevent SQL injection.
- Passwords are hashed with PHP’s `password_hash()`.
- CSRF protection is minimal; the project relies on session validation and SameSite cookie behavior.
- File uploads validate extensions and use `move_uploaded_file()`.
- Messages are scoped to conversation participants; the API verifies `user1_id` / `user2_id` before returning data.

---

## License

This is a student academic project. Feel free to fork and extend it for educational purposes.
