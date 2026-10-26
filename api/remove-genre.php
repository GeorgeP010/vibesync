<?php
/**
 * POST { id: int }
 *   -> { ok: true, id: int }
 */

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$id = isset($INPUT['id']) ? (int) $INPUT['id'] : 0;
if ($id <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Invalid id'], 422);
}

try {
    dbQuery('DELETE FROM genres WHERE id = ? AND user_id = ?', [$id, $USERID]);
    jsonResponse(['ok' => true, 'id' => $id]);
} catch (Throwable $e) {
    error_log('remove-genre: ' . $e->getMessage());
    jsonResponse(['ok' => false, 'error' => 'Server error'], 500);
}
