<?php
/**
 * One-shot helper that creates the demo user and pre-seeds favourites.
 * Run once after importing schema.sql by visiting:
 *
 *     http://localhost/VibeSync-php/sql/seed-demo-user.php
 *
 * Login credentials it creates:
 *     email:    alex@vibesync.app
 *     password: vibesync
 *
 * Safe to re-run: it only creates the user if it doesn't already exist.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

$email = 'alex@vibesync.app';
$pass  = 'vibesync';

try {
    $existing = dbQuery('SELECT id FROM users WHERE email = ?', [$email])->fetch();

    if ($existing) {
        echo "Demo user already exists (id={$existing['id']}). Nothing to do.\n";
        exit;
    }

    $hash = password_hash($pass, PASSWORD_DEFAULT);
    dbQuery(
        'INSERT INTO users (email, password_hash, name, avatar_url, location, festivals_count, friends_count)
         VALUES (?, ?, ?, ?, ?, ?, ?)',
        [
            $email,
            $hash,
            'Alex Vibe',
            'https://lh3.googleusercontent.com/aida-public/AB6AXuDpTVwtWq_ezrNi9iyw_VYBwVcaebRtRYzcW1m1UHSm6PBVnrr9FeMgMp9x39sxmEuLe-zrJX5rcxDO_wSl-QikdhzI7KOyfkptXHplp-j1F97ixMddaJu95yfWHfNgCgLJjX6NJSu-CJXoK_D7Vm762hhCx55MgMm3FGS7mQp2IbnusyqI4FqygGmFLExZ1gT3uztsK96Mk-3FTkk5BdmW4fuK2KY3EfR8mrHVkr7TWW3LhZyxD-39B4_vL7b5xazceLLHIfvZwBE',
            'Los Angeles, CA',
            12,
            48,
        ]
    );

    $userId = (int) db()->lastInsertId();

    dbQuery(
        'INSERT INTO user_settings (user_id, set_alerts, crowd_alerts, safety_mode, friend_tracker)
         VALUES (?, 1, 1, 0, 1)',
        [$userId]
    );

    foreach (['Techno', 'House', 'Trance'] as $g) {
        dbQuery('INSERT INTO genres (user_id, genre_name) VALUES (?, ?)', [$userId, $g]);
    }

    // Pre-favourite a couple of artists if they exist (Charlotte de Witte + Bass Phantom).
    foreach (['charlotte-de-witte', 'bass-phantom'] as $slug) {
        $artist = dbQuery('SELECT id FROM artists WHERE slug = ?', [$slug])->fetch();
        if ($artist) {
            dbQuery('INSERT IGNORE INTO favorites (user_id, artist_id) VALUES (?, ?)', [$userId, $artist['id']]);
        }
    }

    echo "Demo user created.\n";
    echo "  Email:    $email\n";
    echo "  Password: $pass\n";
    echo "You can now log in via login.php.\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
