<?php
/**
 * POST { artist_id: int }
 *   -> { ok: true, is_favorite: bool, artist_id: int }
 */

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$artistId = isset($INPUT['artist_id']) ? (int) $INPUT['artist_id'] : 0;
if ($artistId <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Invalid artist_id'], 422);
}

try {
    // Make sure the artist exists.
    $exists = dbQuery('SELECT id FROM artists WHERE id = ?', [$artistId])->fetch();
    if (!$exists) {
        jsonResponse(['ok' => false, 'error' => 'Artist not found'], 404);
    }

    $isFav = (bool) dbQuery(
        'SELECT 1 FROM favorites WHERE user_id = ? AND artist_id = ?',
        [$USERID, $artistId]
    )->fetchColumn();

    if ($isFav) {
        dbQuery('DELETE FROM favorites WHERE user_id = ? AND artist_id = ?', [$USERID, $artistId]);
        $isFav = false;
    } else {
        dbQuery(
            'INSERT INTO favorites (user_id, artist_id) VALUES (?, ?)',
            [$USERID, $artistId]
        );
        $isFav = true;
    }

    jsonResponse(['ok' => true, 'is_favorite' => $isFav, 'artist_id' => $artistId]);
} catch (Throwable $e) {
    error_log('toggle-favorite: ' . $e->getMessage());
    jsonResponse(['ok' => false, 'error' => 'Server error'], 500);
}
