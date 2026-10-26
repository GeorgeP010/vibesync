<?php
/**
 * POST { key: string, value: bool|"true"|"false"|0|1 }
 *   -> { ok: true, key: string, value: bool }
 *
 * Allowed keys: set_alerts, crowd_alerts, safety_mode, friend_tracker
 */

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$ALLOWED_KEYS = ['set_alerts', 'crowd_alerts', 'safety_mode', 'friend_tracker'];

$key = (string) ($INPUT['key'] ?? '');
if (!in_array($key, $ALLOWED_KEYS, true)) {
    jsonResponse(['ok' => false, 'error' => 'Invalid setting key'], 422);
}

$rawValue = $INPUT['value'] ?? null;
$value    = filter_var($rawValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
if ($value === null) {
    jsonResponse(['ok' => false, 'error' => 'Invalid value'], 422);
}

try {
    // Make sure a settings row exists for this user before updating.
    $exists = (bool) dbQuery('SELECT 1 FROM user_settings WHERE user_id = ?', [$USERID])->fetchColumn();
    if (!$exists) {
        dbQuery(
            'INSERT INTO user_settings (user_id, set_alerts, crowd_alerts, safety_mode, friend_tracker)
             VALUES (?, 1, 1, 0, 1)',
            [$USERID]
        );
    }

    // Column name is whitelisted so it's safe to interpolate.
    $sql = "UPDATE user_settings SET {$key} = ? WHERE user_id = ?";
    dbQuery($sql, [$value ? 1 : 0, $USERID]);

    jsonResponse(['ok' => true, 'key' => $key, 'value' => (bool) $value]);
} catch (Throwable $e) {
    error_log('update-setting: ' . $e->getMessage());
    jsonResponse(['ok' => false, 'error' => 'Server error'], 500);
}
