<?php
/**
 * Authentication & session helpers.
 *
 * Usage at the top of any protected page:
 *     require_once __DIR__ . '/includes/auth.php';
 *     requireLogin();
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------------------------------------------------------------
 * SESSION HELPERS
 * --------------------------------------------------------------- */

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function currentUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/**
 * Fetches the current user record (cached for the request).
 * @return array<string,mixed>|null
 */
function currentUser(): ?array
{
    static $cached = null;

    if (!isLoggedIn()) {
        return null;
    }

    if ($cached !== null) {
        return $cached;
    }

    $stmt = dbQuery('SELECT * FROM users WHERE id = ?', [currentUserId()]);
    $row = $stmt->fetch();
    $cached = $row !== false ? $row : null;

    return $cached;
}

/**
 * Redirect to login if not authenticated. Call this at the very top of
 * every protected page (before any output).
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/* ---------------------------------------------------------------
 * AUTH ACTIONS
 * --------------------------------------------------------------- */

/**
 * @return array{ok: bool, error?: string, user_id?: int}
 */
function registerUser(string $email, string $password, string $name): array
{
    $email = trim($email);
    $name  = trim($name);

    if ($email === '' || $password === '' || $name === '') {
        return ['ok' => false, 'error' => 'Please fill in all fields.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Invalid email address.'];
    }
    if (strlen($password) < 6) {
        return ['ok' => false, 'error' => 'Password must be at least 6 characters.'];
    }

    $existing = dbQuery('SELECT id FROM users WHERE email = ?', [$email])->fetch();
    if ($existing) {
        return ['ok' => false, 'error' => 'An account with that email already exists.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    dbQuery(
        'INSERT INTO users (email, password_hash, name) VALUES (?, ?, ?)',
        [$email, $hash, $name]
    );

    $userId = (int) db()->lastInsertId();

    // Default settings row so toggles always have a value.
    dbQuery(
        'INSERT INTO user_settings (user_id, set_alerts, crowd_alerts, safety_mode, friend_tracker)
         VALUES (?, 1, 1, 0, 1)',
        [$userId]
    );

    return ['ok' => true, 'user_id' => $userId];
}

/**
 * @return array{ok: bool, error?: string, user_id?: int}
 */
function loginUser(string $email, string $password): array
{
    $email = trim($email);
    if ($email === '' || $password === '') {
        return ['ok' => false, 'error' => 'Please enter your email and password.'];
    }

    $user = dbQuery('SELECT id, password_hash FROM users WHERE email = ?', [$email])->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'error' => 'Invalid email or password.'];
    }

    // Mitigate session fixation.
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return ['ok' => true, 'user_id' => (int) $user['id']];
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }
    session_destroy();
}

/* ---------------------------------------------------------------
 * CSRF
 * --------------------------------------------------------------- */

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function verifyCsrf(?string $token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/* ---------------------------------------------------------------
 * OUTPUT HELPERS
 * --------------------------------------------------------------- */

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
