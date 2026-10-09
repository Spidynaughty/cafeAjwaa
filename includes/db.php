<?php
/**
 * PDO connection. Lazily created, reused for the request.
 *
 * Every query in this project goes through prepared statements. There is no
 * function anywhere that concatenates user input into SQL.
 */
declare(strict_types=1);

function db(): ?PDO
{
    static $pdo = null;
    static $tried = false;

    if ($pdo instanceof PDO) {
        return $pdo;
    }
    if ($tried) {
        return null;   // already failed once; don't retry on every call
    }
    $tried = true;

    $file = APP_ROOT . '/config/database.php';
    if (!is_file($file)) {
        return null;   // not configured yet - the site falls back to menu-data.php
    }

    $cfg = require $file;
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['host'], (int) $cfg['port'], $cfg['database'], $cfg['charset']
    );

    try {
        $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]);
    } catch (\Throwable $e) {
        // Catches PDOException (bad credentials/host) AND the base Error that
        // PHP throws for an undefined PDO::MYSQL_ATTR_* constant when the
        // pdo_mysql driver extension itself is missing or disabled. Without
        // this the site did not degrade to data/menu-data.php as documented -
        // it fatally crashed every single page instead.
        // Never leak DSN, credentials or driver text to the browser.
        error_log('[cafe-ajwaa] DB connect failed: ' . $e->getMessage());
        return null;
    }

    return $pdo;
}

/** True when a usable database connection exists. */
function db_available(): bool
{
    return db() instanceof PDO;
}

/** Run a prepared statement and return all rows. */
function db_all(string $sql, array $params = []): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Run a prepared statement and return the first row, or null. */
function db_one(string $sql, array $params = []): ?array
{
    $rows = db_all($sql, $params);
    return $rows[0] ?? null;
}

/** Run a write statement; returns affected row count. */
function db_run(string $sql, array $params = []): int
{
    $pdo = db();
    if (!$pdo) {
        return 0;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}
