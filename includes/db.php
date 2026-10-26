<?php
/**
 * PDO database connection (single instance, lazily constructed).
 * Always use prepared statements via db()->prepare(...).
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Never expose raw stack traces to the user.
        if (defined('APP_DEBUG') && APP_DEBUG === true) {
            http_response_code(500);
            exit('DB connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
        }
        error_log('VibeSync DB error: ' . $e->getMessage());
        http_response_code(500);
        exit('Database connection failed. Please try again later.');
    }

    return $pdo;
}

/**
 * Wrap a query in try/catch so failures don't reveal the schema.
 *
 * @param string                $sql
 * @param array<string,mixed>   $params
 * @return PDOStatement
 */
function dbQuery(string $sql, array $params = []): PDOStatement
{
    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        if (defined('APP_DEBUG') && APP_DEBUG === true) {
            throw $e;
        }
        error_log('VibeSync query error: ' . $e->getMessage() . ' SQL=' . $sql);
        http_response_code(500);
        exit('Something went wrong. Please try again.');
    }
}
