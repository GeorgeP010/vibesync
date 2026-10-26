<?php
/**
 * VibeSync — database connection diagnostic.
 *
 * Visit this file in the browser ONCE while debugging:
 *     https://<your-domain>/<path>/db-test.php
 *
 * It prints:
 *  - the connection settings it is using (password masked),
 *  - whether the PDO connection succeeds,
 *  - a list of tables in the selected database (so you know which DB you're on).
 *
 * DELETE THIS FILE after you're done — it leaks DB layout to anyone who
 * visits the URL.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

require __DIR__ . '/includes/config.php';

echo "VibeSync DB connection test\n";
echo "===========================\n\n";

echo "DB_HOST    = " . DB_HOST . "\n";
echo "DB_NAME    = " . DB_NAME . "\n";
echo "DB_USER    = " . DB_USER . "\n";
echo "DB_PASS    = " . (DB_PASS === '' ? '(empty)' : str_repeat('*', max(3, strlen(DB_PASS)))) . "\n";
echo "DB_CHARSET = " . DB_CHARSET . "\n\n";

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
echo "DSN: $dsn\n\n";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 5,
    ]);
    echo "[OK] PDO connection established.\n\n";

    $stmt = $pdo->query('SELECT DATABASE() AS db, VERSION() AS ver');
    $info = $stmt->fetch();
    echo "Connected to database: {$info['db']}\n";
    echo "MySQL version       : {$info['ver']}\n\n";

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    if (empty($tables)) {
        echo "[WARN] The database is reachable, but no tables exist. Did you import sql/schema.sql?\n";
    } else {
        echo "Tables found (" . count($tables) . "):\n";
        foreach ($tables as $t) {
            echo "  - $t\n";
        }
    }

    echo "\nAll good. Remember to DELETE this file when you're done debugging.\n";
} catch (PDOException $e) {
    echo "[FAIL] PDO error\n";
    echo "  Code:    " . $e->getCode() . "\n";
    echo "  Message: " . $e->getMessage() . "\n\n";

    echo "Common causes:\n";
    echo "  1045 Access denied            -> wrong DB_USER or DB_PASS\n";
    echo "  1044 Access denied to 'X'     -> DB_USER has no rights on DB_NAME\n";
    echo "  1049 Unknown database 'X'     -> DB_NAME does not exist (typo or prefix)\n";
    echo "  2002 / Connection refused     -> wrong DB_HOST or MySQL not running\n";
    echo "  HY000 / unable to connect     -> firewall blocks the host\n";
}
