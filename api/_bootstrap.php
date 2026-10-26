<?php
/**
 * Common bootstrap for every JSON API endpoint.
 * Sets the Content-Type header, parses JSON or form-encoded body, requires
 * an authenticated session, and provides a uniform JSON response helper.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

/**
 * Send a JSON response and exit.
 *
 * @param array<string,mixed> $payload
 */
function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Read POST body — supports both application/json and form-encoded payloads.
 * @return array<string,mixed>
 */
function jsonInput(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'POST required'], 405);
}

if (!isLoggedIn()) {
    jsonResponse(['ok' => false, 'error' => 'Not authenticated'], 401);
}

$INPUT  = jsonInput();
$USERID = currentUserId();
