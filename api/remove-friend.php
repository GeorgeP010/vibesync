<?php
/**
 * POST { user_id: int }
 *   -> { ok: true }
 *
 * Removes the friendship in both directions. Also used to cancel an outgoing
 * pending request.
 */

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$targetId = isset($INPUT['user_id']) ? (int) $INPUT['user_id'] : 0;
if ($targetId <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Invalid user'], 422);
}

try {
    dbQuery(
        'DELETE FROM friends
          WHERE (user_id = ? AND friend_id = ?)
             OR (user_id = ? AND friend_id = ?)',
        [$USERID, $targetId, $targetId, $USERID]
    );
    jsonResponse(['ok' => true]);
} catch (Throwable $e) {
    error_log('remove-friend: ' . $e->getMessage());
    jsonResponse(['ok' => false, 'error' => 'Server error'], 500);
}
