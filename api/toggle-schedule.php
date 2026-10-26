<?php
/**
 * POST { performance_id: int }
 *   -> { ok: true, is_scheduled: bool, performance_id: int }
 */

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$performanceId = isset($INPUT['performance_id']) ? (int) $INPUT['performance_id'] : 0;
if ($performanceId <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Invalid performance_id'], 422);
}

try {
    $exists = dbQuery('SELECT id FROM performances WHERE id = ?', [$performanceId])->fetch();
    if (!$exists) {
        jsonResponse(['ok' => false, 'error' => 'Performance not found'], 404);
    }

    $alreadyScheduled = (bool) dbQuery(
        'SELECT 1 FROM user_schedule WHERE user_id = ? AND performance_id = ?',
        [$USERID, $performanceId]
    )->fetchColumn();

    if ($alreadyScheduled) {
        dbQuery(
            'DELETE FROM user_schedule WHERE user_id = ? AND performance_id = ?',
            [$USERID, $performanceId]
        );
        $alreadyScheduled = false;
    } else {
        dbQuery(
            'INSERT INTO user_schedule (user_id, performance_id) VALUES (?, ?)',
            [$USERID, $performanceId]
        );
        $alreadyScheduled = true;
    }

    jsonResponse([
        'ok'             => true,
        'is_scheduled'   => $alreadyScheduled,
        'performance_id' => $performanceId,
    ]);
} catch (Throwable $e) {
    error_log('toggle-schedule: ' . $e->getMessage());
    jsonResponse(['ok' => false, 'error' => 'Server error'], 500);
}
