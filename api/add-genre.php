<?php
/**
 * POST { name: string }
 *   -> { ok: true, genre: { id, name } }
 */

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$name = isset($INPUT['name']) ? trim((string) $INPUT['name']) : '';
if ($name === '' || mb_strlen($name) > 80) {
    jsonResponse(['ok' => false, 'error' => 'Genre must be 1-80 characters.'], 422);
}

try {
    // Avoid exact-duplicate genre per user (case-insensitive).
    $existing = dbQuery(
        'SELECT id, genre_name FROM genres WHERE user_id = ? AND LOWER(genre_name) = LOWER(?)',
        [$USERID, $name]
    )->fetch();

    if ($existing) {
        jsonResponse([
            'ok'    => true,
            'genre' => ['id' => (int) $existing['id'], 'name' => $existing['genre_name']],
            'duplicate' => true,
        ]);
    }

    dbQuery('INSERT INTO genres (user_id, genre_name) VALUES (?, ?)', [$USERID, $name]);
    $id = (int) db()->lastInsertId();

    jsonResponse([
        'ok'    => true,
        'genre' => ['id' => $id, 'name' => $name],
    ]);
} catch (Throwable $e) {
    error_log('add-genre: ' . $e->getMessage());
    jsonResponse(['ok' => false, 'error' => 'Server error'], 500);
}
