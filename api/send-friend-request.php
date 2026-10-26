<?php
/**
 * POST { user_id: int }
 *   -> { ok: true, status: 'pending'|'accepted' }
 *
 * Sends a friend request. If the other user already invited us first, the
 * request is auto-accepted.
 */

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$targetId = isset($INPUT['user_id']) ? (int) $INPUT['user_id'] : 0;
if ($targetId <= 0 || $targetId === $USERID) {
    jsonResponse(['ok' => false, 'error' => 'Invalid user'], 422);
}

try {
    $exists = dbQuery('SELECT id FROM users WHERE id = ?', [$targetId])->fetch();
    if (!$exists) {
        jsonResponse(['ok' => false, 'error' => 'User not found'], 404);
    }

    // Look at any existing relationship row in either direction.
    $rows = dbQuery(
        'SELECT user_id, friend_id, status
           FROM friends
          WHERE (user_id = ? AND friend_id = ?)
             OR (user_id = ? AND friend_id = ?)',
        [$USERID, $targetId, $targetId, $USERID]
    )->fetchAll();

    $haveAccepted = false;
    $incomingPending = false;
    $outgoingPending = false;

    foreach ($rows as $r) {
        if ($r['status'] === 'accepted') {
            $haveAccepted = true;
        }
        if ((int) $r['user_id'] === $targetId && $r['status'] === 'pending') {
            $incomingPending = true;
        }
        if ((int) $r['user_id'] === $USERID && $r['status'] === 'pending') {
            $outgoingPending = true;
        }
    }

    if ($haveAccepted) {
        jsonResponse(['ok' => false, 'error' => 'Already friends'], 409);
    }
    if ($outgoingPending) {
        jsonResponse(['ok' => false, 'error' => 'Request already sent'], 409);
    }

    if ($incomingPending) {
        // The other side already invited us → auto-accept and create the reverse row.
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

    dbQuery(
        'INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, ?)',
        [$USERID, $targetId, 'pending']
    );

    jsonResponse(['ok' => true, 'status' => 'pending']);
} catch (Throwable $e) {
    error_log('send-friend-request: ' . $e->getMessage());
    jsonResponse(['ok' => false, 'error' => 'Server error'], 500);
}
