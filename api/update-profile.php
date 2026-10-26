<?php
/**
 * POST { name?: string, location?: string, avatar_url?: string }
 *   -> { ok: true, user: { id, name, location, avatar_url } }
 *
 * Updates fields the user is allowed to edit on their own profile.
 */

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$name      = isset($INPUT['name'])       ? trim((string) $INPUT['name'])       : null;
$location  = isset($INPUT['location'])   ? trim((string) $INPUT['location'])   : null;
$avatarUrl = isset($INPUT['avatar_url']) ? trim((string) $INPUT['avatar_url']) : null;

$updates = [];
$params  = [];

if ($name !== null) {
    if ($name === '' || mb_strlen($name) > 120) {
        jsonResponse(['ok' => false, 'error' => 'Name must be 1-120 characters.'], 422);
    }
    $updates[]   = 'name = :name';
    $params[':name'] = $name;
}

if ($location !== null) {
    if (mb_strlen($location) > 120) {
        jsonResponse(['ok' => false, 'error' => 'Location is too long.'], 422);
    }
    $updates[]       = 'location = :location';
    $params[':location'] = $location;
}

if ($avatarUrl !== null) {
    if ($avatarUrl !== '' && !filter_var($avatarUrl, FILTER_VALIDATE_URL)) {
        jsonResponse(['ok' => false, 'error' => 'Avatar URL is invalid.'], 422);
    }
    if (mb_strlen($avatarUrl) > 500) {
        jsonResponse(['ok' => false, 'error' => 'Avatar URL is too long.'], 422);
    }
    $updates[]            = 'avatar_url = :avatar';
    $params[':avatar']    = $avatarUrl !== '' ? $avatarUrl : null;
}

if (empty($updates)) {
    jsonResponse(['ok' => false, 'error' => 'Nothing to update.'], 422);
}

try {
    $params[':uid'] = $USERID;
    $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = :uid';
    dbQuery($sql, $params);

    $user = dbQuery(
        'SELECT id, name, location, avatar_url FROM users WHERE id = ?',
        [$USERID]
    )->fetch();

    jsonResponse(['ok' => true, 'user' => $user ?: null]);
} catch (Throwable $e) {
    error_log('update-profile: ' . $e->getMessage());
    jsonResponse(['ok' => false, 'error' => 'Server error'], 500);
}
