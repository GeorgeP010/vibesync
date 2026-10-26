<?php
/**
 * POST { query: string, type?: 'artists'|'stages' }
 *   -> { ok: true, results: [...] }
 */

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$query = trim((string) ($INPUT['query'] ?? ''));
$type  = (string) ($INPUT['type'] ?? 'artists');

if ($query === '') {
    jsonResponse(['ok' => true, 'results' => []]);
}

$like = '%' . $query . '%';

try {
    if ($type === 'stages') {
        $rows = dbQuery(
            'SELECT id, name, description, icon
               FROM stages
              WHERE name LIKE ? OR description LIKE ?
              ORDER BY name ASC
              LIMIT 25',
            [$like, $like]
        )->fetchAll();
    } else {
        $rows = dbQuery(
            'SELECT id, slug, name, genre, image_url, vibe_match
               FROM artists
              WHERE name LIKE ? OR genre LIKE ?
              ORDER BY vibe_match DESC, name ASC
              LIMIT 25',
            [$like, $like]
        )->fetchAll();
    }

    jsonResponse(['ok' => true, 'type' => $type, 'results' => $rows]);
} catch (Throwable $e) {
    error_log('search: ' . $e->getMessage());
    jsonResponse(['ok' => false, 'error' => 'Server error'], 500);
}
