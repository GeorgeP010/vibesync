<?php
/**
 * Reusable data-access helpers used by the page templates.
 * Returns plain associative arrays so the views stay simple.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * @return array<int,array<string,mixed>>
 */
function fetchArtists(?string $search = null, ?string $tag = null): array
{
    $sql    = 'SELECT * FROM artists';
    $params = [];
    $where  = [];

    if ($search !== null && $search !== '') {
        $where[]            = '(name LIKE :q OR genre LIKE :q)';
        $params[':q']       = '%' . $search . '%';
    }
    if ($tag !== null && $tag !== '' && strtolower($tag) !== 'all') {
        $where[]      = 'genre LIKE :tag';
        $params[':tag'] = '%' . $tag . '%';
    }

    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY vibe_match DESC, name ASC';

    return dbQuery($sql, $params)->fetchAll();
}

/** @return array<int,array<string,mixed>> */
function fetchUserFavoriteIds(int $userId): array
{
    $rows = dbQuery('SELECT artist_id FROM favorites WHERE user_id = ?', [$userId])->fetchAll();
    return array_map(static fn ($r) => (int) $r['artist_id'], $rows);
}

/** @return array<int,array<string,mixed>> */
function fetchUserScheduledPerformances(int $userId, ?string $dayLabel = null): array
{
    $sql = 'SELECT p.id AS performance_id, p.start_time, p.end_time, p.day_label, p.has_clash,
                   a.id AS artist_id, a.slug, a.name AS artist_name, a.genre, a.image_url,
                   s.name AS stage_name
              FROM user_schedule us
              JOIN performances p ON p.id = us.performance_id
              JOIN artists a ON a.id = p.artist_id
              JOIN stages  s ON s.id = p.stage_id
             WHERE us.user_id = :uid';
    $params = [':uid' => $userId];

    if ($dayLabel !== null && $dayLabel !== '') {
        $sql .= ' AND p.day_label = :day';
        $params[':day'] = $dayLabel;
    }
    $sql .= ' ORDER BY p.start_time ASC';

    return dbQuery($sql, $params)->fetchAll();
}

/** @return array<int,int>  performance_ids the user has scheduled */
function fetchUserScheduledPerformanceIds(int $userId): array
{
    $rows = dbQuery('SELECT performance_id FROM user_schedule WHERE user_id = ?', [$userId])->fetchAll();
    return array_map(static fn ($r) => (int) $r['performance_id'], $rows);
}

/** @return array<int,array<string,mixed>> */
function fetchPerformancesByDay(string $dayLabel): array
{
    return dbQuery(
        'SELECT p.id AS performance_id, p.start_time, p.end_time, p.day_label, p.has_clash,
                a.id AS artist_id, a.slug, a.name AS artist_name, a.genre, a.image_url,
                s.name AS stage_name
           FROM performances p
           JOIN artists a ON a.id = p.artist_id
           JOIN stages  s ON s.id = p.stage_id
          WHERE p.day_label = ?
          ORDER BY p.start_time ASC',
        [$dayLabel]
    )->fetchAll();
}

/** @return array<int,array<string,mixed>> */
function fetchAllDays(): array
{
    return dbQuery(
        'SELECT day_label, MIN(start_time) AS first_time
           FROM performances
       GROUP BY day_label
       ORDER BY first_time ASC'
    )->fetchAll();
}

/** Returns the performance currently live (NOW between start/end) or null. */
function fetchNowPlaying(): ?array
{
    $row = dbQuery(
        'SELECT p.id AS performance_id, p.start_time, p.end_time, p.day_label,
                a.id AS artist_id, a.slug, a.name AS artist_name, a.genre, a.image_url,
                s.name AS stage_name
           FROM performances p
           JOIN artists a ON a.id = p.artist_id
           JOIN stages  s ON s.id = p.stage_id
          WHERE NOW() BETWEEN p.start_time AND p.end_time
          ORDER BY p.start_time ASC
          LIMIT 1'
    )->fetch();
    return $row !== false ? $row : null;
}

/** @return array<int,array<string,mixed>> Up next performances */
function fetchUpNext(int $limit = 3): array
{
    return dbQuery(
        'SELECT p.id AS performance_id, p.start_time, p.end_time, p.day_label,
                a.id AS artist_id, a.slug, a.name AS artist_name, a.genre, a.image_url,
                s.name AS stage_name
           FROM performances p
           JOIN artists a ON a.id = p.artist_id
           JOIN stages  s ON s.id = p.stage_id
          WHERE p.start_time >= NOW()
          ORDER BY p.start_time ASC
          LIMIT ' . (int) $limit
    )->fetchAll();
}

/** @return array<int,array<string,mixed>> */
function fetchQueues(?string $type = null): array
{
    $sql = 'SELECT * FROM queues';
    $params = [];
    if ($type !== null && $type !== '' && strtolower($type) !== 'all') {
        $sql .= ' WHERE type = ?';
        $params[] = $type;
    }
    $sql .= ' ORDER BY wait_minutes DESC';
    return dbQuery($sql, $params)->fetchAll();
}

/**
 * Returns the user_settings row, creating defaults on the fly if missing.
 * @return array<string,mixed>
 */
function fetchUserSettings(int $userId): array
{
    $row = dbQuery('SELECT * FROM user_settings WHERE user_id = ?', [$userId])->fetch();
    if (!$row) {
        dbQuery(
            'INSERT INTO user_settings (user_id, set_alerts, crowd_alerts, safety_mode, friend_tracker)
             VALUES (?, 1, 1, 0, 1)',
            [$userId]
        );
        $row = dbQuery('SELECT * FROM user_settings WHERE user_id = ?', [$userId])->fetch();
    }
    return $row ?: [
        'set_alerts'     => 1,
        'crowd_alerts'   => 1,
        'safety_mode'    => 0,
        'friend_tracker' => 1,
    ];
}

/** @return array<int,string> genre names for a user */
function fetchUserGenres(int $userId): array
{
    $rows = dbQuery('SELECT genre_name FROM genres WHERE user_id = ? ORDER BY id ASC', [$userId])->fetchAll();
    return array_map(static fn ($r) => (string) $r['genre_name'], $rows);
}

/**
 * Builds a JSON-encoded artist object for the client-side modal data attribute.
 * Returns a string ready to drop into  data-artist='...'  with htmlspecialchars escaping.
 *
 * @param array<string,mixed> $row  result row from joined performance/artist query
 */
function artistDataAttribute(array $row): string
{
    $artist = [
        'id'              => $row['slug']         ?? ($row['artist_slug'] ?? ''),
        'artist_id'       => isset($row['artist_id']) ? (int) $row['artist_id'] : null,
        'performance_id'  => isset($row['performance_id']) ? (int) $row['performance_id'] : null,
        'name'            => $row['artist_name']  ?? ($row['name'] ?? ''),
        'genre'           => $row['genre']        ?? '',
        'stage'           => $row['stage_name']   ?? '',
        'time'            => formatTimeRange($row['start_time'] ?? null, $row['end_time'] ?? null),
        'image'           => $row['image_url']    ?? '',
    ];
    return htmlspecialchars(json_encode($artist, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
}

function formatTimeRange(?string $start, ?string $end): string
{
    if (!$start) return '';
    $startStr = date('H:i', strtotime($start));
    $endStr   = $end ? date('H:i', strtotime($end)) : '';
    return $endStr ? "$startStr - $endStr" : $startStr;
}

function formatDayBadge(string $dayLabel): array
{
    // "Friday Mar 12" -> ["Fri", "12"]
    if (preg_match('/^(\w{3})\w+\s+\w+\s+(\d+)/', $dayLabel, $m)) {
        return [strtoupper($m[1]), $m[2]];
    }
    return [substr($dayLabel, 0, 3), ''];
}
