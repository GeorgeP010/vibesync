-- =============================================================
-- VibeSync database schema + seed data
-- Compatible with MySQL 5.7+ / 8.0+
--
-- HOW TO RUN
-- ----------
-- This script does NOT create or select a database, so it works on shared
-- hosting where your MySQL user has no CREATE DATABASE privilege.
--
-- 1. In phpMyAdmin, click your existing database in the left sidebar so it
--    is highlighted (the breadcrumb at the top should read
--    "Server: ... » Database: <your-db-name>").
-- 2. Open the "SQL" tab *inside* that database and run this file.
-- 3. Update includes/config.php so DB_NAME matches the actual database name
--    your hosting created for you (it may be prefixed, e.g. "vibesync_db").
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- Drop tables if they exist (so this file is re-runnable)
-- -------------------------------------------------------------
DROP TABLE IF EXISTS friends;
DROP TABLE IF EXISTS queues;
DROP TABLE IF EXISTS genres;
DROP TABLE IF EXISTS user_settings;
DROP TABLE IF EXISTS user_schedule;
DROP TABLE IF EXISTS favorites;
DROP TABLE IF EXISTS performances;
DROP TABLE IF EXISTS artists;
DROP TABLE IF EXISTS stages;
DROP TABLE IF EXISTS users;

-- -------------------------------------------------------------
-- USERS
-- -------------------------------------------------------------
CREATE TABLE users (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email           VARCHAR(190) NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,
  name            VARCHAR(120) NOT NULL,
  avatar_url      VARCHAR(500) DEFAULT NULL,
  location        VARCHAR(120) DEFAULT NULL,
  festivals_count INT UNSIGNED NOT NULL DEFAULT 0,
  friends_count   INT UNSIGNED NOT NULL DEFAULT 0,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- STAGES
-- -------------------------------------------------------------
CREATE TABLE stages (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(120) NOT NULL,
  description TEXT,
  map_x       DECIMAL(5,2) DEFAULT NULL,
  map_y       DECIMAL(5,2) DEFAULT NULL,
  icon        VARCHAR(60)  DEFAULT 'graphic_eq',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- ARTISTS
-- -------------------------------------------------------------
CREATE TABLE artists (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug        VARCHAR(120) NOT NULL,
  name        VARCHAR(160) NOT NULL,
  genre       VARCHAR(80)  NOT NULL,
  image_url   VARCHAR(500) DEFAULT NULL,
  bio         TEXT,
  vibe_match  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uk_artists_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- PERFORMANCES (one set on one stage at one time)
-- -------------------------------------------------------------
CREATE TABLE performances (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  artist_id   INT UNSIGNED NOT NULL,
  stage_id    INT UNSIGNED NOT NULL,
  start_time  DATETIME NOT NULL,
  end_time    DATETIME NOT NULL,
  day_label   VARCHAR(40) NOT NULL,
  has_clash   TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY ix_perf_artist (artist_id),
  KEY ix_perf_stage  (stage_id),
  KEY ix_perf_start  (start_time),
  CONSTRAINT fk_perf_artist FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE,
  CONSTRAINT fk_perf_stage  FOREIGN KEY (stage_id)  REFERENCES stages(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- FAVORITES (user <-> artist)
-- -------------------------------------------------------------
CREATE TABLE favorites (
  user_id    INT UNSIGNED NOT NULL,
  artist_id  INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, artist_id),
  CONSTRAINT fk_fav_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
  CONSTRAINT fk_fav_artist FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- USER SCHEDULE (user <-> performance)
-- -------------------------------------------------------------
CREATE TABLE user_schedule (
  user_id        INT UNSIGNED NOT NULL,
  performance_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, performance_id),
  CONSTRAINT fk_sch_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_sch_perf FOREIGN KEY (performance_id) REFERENCES performances(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- USER SETTINGS
-- -------------------------------------------------------------
CREATE TABLE user_settings (
  user_id        INT UNSIGNED NOT NULL,
  set_alerts     TINYINT(1) NOT NULL DEFAULT 1,
  crowd_alerts   TINYINT(1) NOT NULL DEFAULT 1,
  safety_mode    TINYINT(1) NOT NULL DEFAULT 0,
  friend_tracker TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_settings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- GENRES (favourite genres per user)
-- -------------------------------------------------------------
CREATE TABLE genres (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  genre_name VARCHAR(80) NOT NULL,
  PRIMARY KEY (id),
  KEY ix_genres_user (user_id),
  CONSTRAINT fk_genres_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- QUEUES (live wait times)
-- -------------------------------------------------------------
CREATE TABLE queues (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name             VARCHAR(120) NOT NULL,
  location         VARCHAR(120) NOT NULL,
  wait_minutes     INT UNSIGNED NOT NULL DEFAULT 0,
  capacity_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
  icon             VARCHAR(60)  DEFAULT 'local_bar',
  type             ENUM('bar','toilet','food','merch','water','medical') NOT NULL DEFAULT 'bar',
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- FRIENDS
-- -------------------------------------------------------------
CREATE TABLE friends (
  user_id   INT UNSIGNED NOT NULL,
  friend_id INT UNSIGNED NOT NULL,
  status    ENUM('pending','accepted') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (user_id, friend_id),
  CONSTRAINT fk_friends_user   FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_friends_friend FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- SEED DATA
-- =============================================================

-- Demo user is created by sql/seed-demo-user.php (so the bcrypt hash is real).
-- Email: alex@vibesync.app, Password: vibesync
-- After importing this schema, open http://localhost/VibeSync-php/sql/seed-demo-user.php
-- once to create the demo account, OR register a fresh user via register.php.

-- ----- STAGES -----
INSERT INTO stages (id, name, description, map_x, map_y, icon) VALUES
(1, 'Main Stage',       'The biggest stage, headliners only',  45.00, 30.00, 'graphic_eq'),
(2, 'Neon Garden',      'Outdoor stage with light installations', 25.00, 55.00, 'speaker'),
(3, 'Bass Arena',       'Heavy bass and dubstep cathedral',     70.00, 45.00, 'subwoofer'),
(4, 'Circuit Grounds',  'Tech-house & techno warehouse',        55.00, 70.00, 'electric_bolt'),
(5, 'Quantum Valley',   'Melodic techno in a forest setting',   15.00, 25.00, 'forest');

-- ----- ARTISTS -----
INSERT INTO artists (id, slug, name, genre, image_url, bio, vibe_match) VALUES
(1,  'charlotte-de-witte', 'Charlotte de Witte', 'Techno',
     'https://lh3.googleusercontent.com/aida-public/AB6AXuDe-qY3LOvkiUHaM5yUia8qGBl3zrf_E00L8RVF-0G0ls2KJzJFDmQcy0mrUGwd8MTx7k9oY3jvEk6Mcr2Dbb_96EF3Npe1xQL7esaTYV8nXZZgESbBpnGidNnAZGLCA_qagI1Sf6nJOLwGM933uQXVHje0SZaHfVzb5apjSkO-TuTgWFy2y3Ta5rSXE0qt4pABJcvtNbuVF5Gc7cMpmL_Z_4jWE3RE-Z2hh89rqEo28aV_7OPiv52iCivUlMO6_B81c8y4y1HrPgM',
     'Belgian techno powerhouse', 98),
(2,  'bicep', 'BICEP', 'Electronic',
     'https://lh3.googleusercontent.com/aida-public/AB6AXuCcNQeZ8_lg0R7a96p1nIiEGVvrO_LWOaIqG5URz-BJu6FeBv4S6S0FdjrtocKG0RTo8MiMI4fOUkX3jWWrQLQ1oV4KEP59TDNvaMlnZOJ0EXJCNzMHO5l2a_9efAJpHoeR2yXA_e69n-17sSkGc48ivanrEHjqJcDzxfCLdFRTmC4wIgtPhUueee6oWVYP1N6yZ_wl0L-bEDo3tDB8JPEEUikURGlF_gPJmEF3a7T8mYT75BxAUfHbJIe988mVawAL7UPt0OljFl4',
     'Belfast electronic duo', 85),
(3,  'peggy-gou', 'Peggy Gou', 'House',
     'https://lh3.googleusercontent.com/aida-public/AB6AXuCscqzXeE0KOnhHEl5EnsodD3o21EGnBwtMUDpVQiIQwwzpXucqCOMxJYefm21wE5f77Uk375Ji7eWkLyF_dYGkb71IVvhmZsBdB_Aac2cXeg824M5CMovaiPaxJ6xhNVMVQh8uNv3TGGj4SudrNQ06_McYOHWBVM8T5CVePy2-phzU46SsXPRyONIJDw5CxMsPOyd_sU8fcvWdwxd1Y_svIg3-MAZw9B90GMWE93E86jIl451UAN11ImIRSnjDD2pxa-x2KVeWHvE',
     'Berlin-based house DJ', 82),
(4,  'tale-of-us', 'Tale Of Us', 'Melodic Techno',
     'https://lh3.googleusercontent.com/aida-public/AB6AXuAZ-s0_gOx-Wtbtep7G_VdouZptbXY-KiMZWyaeWfZoz1oUEi217bxGmETnGLA2CHDxisdZ-IoOW7h4BofixWjl4Rh7pLH8rOy2tZnBSJj9el58Mz6jtB_VH-FGAusg3_nqWHP5OzITIxVA_CfE7hA-B15eqmGv_UwJdmvvTKGBWMIoUgkJBIwLLRBU4DMUuUhbhGD7OA4FU77iSnzMfiuyD6dCSYO9dt6QIvkN9--HE8hmbC5s1H_SFK_NCX3ZScNoFLhnTG1XSgY',
     'Afterlife founders', 79),
(5,  'digital-echoes', 'Digital Echoes', 'Bass Music',  NULL, 'Bass collective from Bristol', 76),
(6,  'the-midnight',   'The Midnight',   'Synthwave',   NULL, 'Synthwave duo', 88),
(7,  'aurora-wave',    'Aurora Wave',    'Ambient Techno', NULL, 'Nordic ambient project', 73),
(8,  'neon-horizon',   'Neon Horizon',   'House',       NULL, 'Disco-house newcomer', 71),
(9,  'bass-phantom',   'Bass Phantom',   'Dubstep',     NULL, 'Heavy dubstep act', 68),
(10, 'luna-echo',      'Luna Echo',      'Progressive', NULL, 'Progressive trance trio', 65),
(11, 'glitch-mob',     'The Glitch Mob', 'Electronic',  NULL, 'LA electronic trio', 91);

-- ----- PERFORMANCES -----
-- FRIDAY 12 MARCH
INSERT INTO performances (artist_id, stage_id, start_time, end_time, day_label, has_clash) VALUES
(8,  1, '2026-03-12 14:00:00', '2026-03-12 15:30:00', 'Friday Mar 12', 0),
(9,  3, '2026-03-12 16:00:00', '2026-03-12 17:30:00', 'Friday Mar 12', 1),
(10, 5, '2026-03-12 16:00:00', '2026-03-12 17:00:00', 'Friday Mar 12', 1),
(11, 1, '2026-03-12 18:00:00', '2026-03-12 19:30:00', 'Friday Mar 12', 0),
(5,  3, '2026-03-12 21:00:00', '2026-03-12 22:30:00', 'Friday Mar 12', 0),
(6,  2, '2026-03-12 22:45:00', '2026-03-13 00:00:00', 'Friday Mar 12', 0),
(7,  5, '2026-03-13 00:30:00', '2026-03-13 02:00:00', 'Friday Mar 12', 0),
(1,  1, '2026-03-12 23:30:00', '2026-03-13 01:00:00', 'Friday Mar 12', 0);

-- SATURDAY 13 MARCH
INSERT INTO performances (artist_id, stage_id, start_time, end_time, day_label, has_clash) VALUES
(2,  2, '2026-03-13 21:00:00', '2026-03-13 22:30:00', 'Saturday Mar 13', 0),
(3,  4, '2026-03-13 22:15:00', '2026-03-13 23:45:00', 'Saturday Mar 13', 0),
(11, 1, '2026-03-13 18:00:00', '2026-03-13 19:30:00', 'Saturday Mar 13', 0),
(8,  3, '2026-03-13 16:00:00', '2026-03-13 17:30:00', 'Saturday Mar 13', 0);

-- SUNDAY 14 MARCH
INSERT INTO performances (artist_id, stage_id, start_time, end_time, day_label, has_clash) VALUES
(4,  5, '2026-03-14 20:45:00', '2026-03-14 22:15:00', 'Sunday Mar 14', 0),
(1,  1, '2026-03-14 23:00:00', '2026-03-15 00:30:00', 'Sunday Mar 14', 0),
(6,  2, '2026-03-14 21:30:00', '2026-03-14 23:00:00', 'Sunday Mar 14', 0),
(5,  3, '2026-03-14 19:00:00', '2026-03-14 20:30:00', 'Sunday Mar 14', 0);

-- ----- QUEUES -----
INSERT INTO queues (name, location, wait_minutes, capacity_percent, icon, type) VALUES
('Main Bar',       'North Village',   25, 85, 'local_bar',         'bar'),
('Toilets',        'East Stage',       5, 20, 'wc',                'toilet'),
('Food Village',   'Central Plaza',   15, 60, 'restaurant',        'food'),
('Official Merch', 'South Entrance',  10, 35, 'checkroom',         'merch'),
('Water Station',  'Near Main Stage',  2, 10, 'water_drop',        'water'),
('Medical Tent',   'West Entrance',    0,  5, 'medical_services',  'medical');

-- (Favorites for the demo user are added by sql/seed-demo-user.php)
