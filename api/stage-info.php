<?php
/**
 * POST { stage_id: int }
 *   -> {
 *        ok: true,
 *        stage:   { id, name, description, icon },
 *        now_playing: {...}|null,
 *        upcoming:    [...]
 *      }
 *
 * Used by the festival map: clicking a stage marker shows what's currently
 * playing there and the next handful of sets.
 */

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$stageId = isset($INPUT['stage_id']) ? (int) $INPUT['stage_id'] : 0;
if ($stageId <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Invalid stage_id'], 422);
}

try {
    $stage = dbQuery(
        'SELECT id, name, description, icon FROM stages WHERE id = ?',
        [$stageId]
    )->fetch();

    if (!$stage) {
        jsonResponse(['ok' => false, 'error' => 'Stage not found'], 404);
    }

    $nowPlaying = dbQuery(
        'SELECT p.id AS performance_id, p.start_time, p.end_time, p.day_label,
                a.id AS artist_id, a.slug, a.name AS artist_name, a.genre, a.image_url
           FROM performances p
           JOIN artists a ON a.id = p.artist_id
          WHERE p.stage_id = ?
            AND NOW() BETWEEN p.start_time AND p.end_time
          LIMIT 1',
        [$stageId]
    )->fetch();

    $upcoming = dbQuery(
        'SELECT p.id AS performance_id, p.start_time, p.end_time, p.day_label,
                a.id AS artist_id, a.slug, a.name AS artist_name, a.genre, a.image_url
           FROM performances p
           JOIN artists a ON a.id = p.artist_id
          WHERE p.stage_id = ?
            AND p.start_time >= NOW()
          ORDER BY p.start_time ASC
          LIMIT 4',
        [$stageId]
    )->fetchAll();

    $favoriteRows = dbQuery(
        'SELECT artist_id FROM favorites WHERE user_id = ?',
        [$USERID]
    )->fetchAll();
    $favoriteIds = array_map(static fn ($r) => (int) $r['artist_id'], $favoriteRows);

    $scheduledRows = dbQuery(
        'SELECT performance_id FROM user_schedule WHERE user_id = ?',
        [$USERID]
    )->fetchAll();
    $scheduledIds = array_map(static fn ($r) => (int) $r['performance_id'], $scheduledRows);

    $augment = static function (array $row) use ($favoriteIds, $scheduledIds): array {
        $row['is_favorite']  = in_array((int) $row['artist_id'], $favoriteIds, true);
        $row['is_scheduled'] = in_array((int) $row['performance_id'], $scheduledIds, true);
        $row['time_label']   = date('H:i', strtotime($row['start_time']))
                              . ' - ' . date('H:i', strtotime($row['end_time']));
        return $row;
    };

    jsonResponse([
        'ok'          => true,
        'stage'       => $stage,
        'now_playing' => $nowPlaying ? $augment($nowPlaying) : null,
        'upcoming'    => array_map($augment, $upcoming),
    ]);
} catch (Throwable $e) {
    error_log('stage-info: ' . $e->getMessage());
    jsonResponse(['ok' => false, 'error' => 'Server error'], 500);
}
