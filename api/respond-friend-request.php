<?php
/**
 * POST { user_id: int, action: 'accept'|'decline' }
 *   -> { ok: true, status: 'accepted'|'declined' }
 *
 * Accepts or declines a *pending* invite that was sent TO us by user_id.
 */

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$targetId = isset($INPUT['user_id']) ? (int) $INPUT['user_id'] : 0;
$action   = (string) ($INPUT['action'] ?? '');

if ($targetId <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Invalid user'], 422);
}
if (!in_array($action, ['accept', 'decline'], true)) {
    jsonResponse(['ok' => false, 'error' => 'Invalid action'], 422);
}

try {
    $req = dbQuery(
        'SELECT 1 FROM friends WHERE user_id = ? AND friend_id = ? AND status = ?',
        [$targetId, $USERID, 'pending']
    )->fetch();

    if (!$req) {
        jsonResponse(['ok' => false, 'error' => 'No pending request from this user'], 404);
    }

    if ($action === 'accept') {
        db()->beginTransaction();
        dbQuery(
            'UPDATE friends SET status = ? WHERE user_id = ? AND friend_id = ?',
            ['accepted', $targetId, $USERID]
        );
        dbQuery(
            'INSERT IGNORE INTO friends (user_id, friend_id, status) VALUES (?, ?, ?)',
            [$USERID, $targetId, 'accepted']
        );
        db()->commit();
        jsonResponse(['ok' => true, 'status' => 'accepted']);
    }

    // decline
    dbQuery(
        'DELETE FROM friends WHERE user_id = ? AND friend_id = ?',
        [$targetId, $USERID]
    );
    jsonResponse(['ok' => true, 'status' => 'declined']);
} catch (Throwable $e) {
    error_log('respond-friend-request: ' . $e->getMessage());
    jsonResponse(['ok' => false, 'error' => 'Server error'], 500);
}
