<?php
/**
 * POST { query: string }
 *   -> { ok: true, results: [{ id, name, email, avatar_url }, ...] }
 *
 * Used by friends.php to find people to add. Excludes the current user and
 * anyone we already have a relationship with (in either direction).
 */

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$query = trim((string) ($INPUT['query'] ?? ''));
if (mb_strlen($query) < 2) {
    jsonResponse(['ok' => true, 'results' => []]);
}

$like = '%' . $query . '%';

try {
    $rows = dbQuery(
        'SELECT id, name, email, avatar_url, location
           FROM users
          WHERE id <> ?
            AND (name LIKE ? OR email LIKE ?)
            AND id NOT IN (
                SELECT friend_id FROM friends WHERE user_id = ?
                UNION
                SELECT user_id   FROM friends WHERE friend_id = ?
            )
          ORDER BY name ASC
          LIMIT 20',
        [$USERID, $like, $like, $USERID, $USERID]
    )->fetchAll();

    jsonResponse(['ok' => true, 'results' => $rows]);
} catch (Throwable $e) {
    error_log('search-users: ' . $e->getMessage());
    jsonResponse(['ok' => false, 'error' => 'Server error'], 500);
}
