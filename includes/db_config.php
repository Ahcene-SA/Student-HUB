<?php
/**
 * Local environment overrides (MAMP, WAMP, etc.)
 * If this file exists it sets DB_* env vars before production fallbacks kick in.
 */
if (file_exists(__DIR__ . '/local.env.php')) {
    require_once __DIR__ . '/local.env.php';
}

/**
 * Central database configuration.
 *
 * Production defaults point to the Dokploy MySQL service.
 * For local WAMP development, set Apache environment variables or temporarily
 * change the fallback values below back to 127.0.0.1 / root / '' / devweb.
 */
function get_db_connection() {
    $host = getenv('DB_HOST') ?: 'studenthub-studenthubdb-swptga';
    $user = getenv('DB_USER') ?: 'adminSQL';
    $pass = getenv('DB_PASS') ?: 'ahcenesalim0208';
    $db   = getenv('DB_NAME') ?: 'StudenthubDB';
    $port = getenv('DB_PORT') ?: 3306;

    $conn = new mysqli($host, $user, $pass, $db, (int) $port);

    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

function get_pdo_connection() {
    $host = getenv('DB_HOST') ?: 'studenthub-studenthubdb-swptga';
    $user = getenv('DB_USER') ?: 'adminSQL';
    $pass = getenv('DB_PASS') ?: 'ahcenesalim0208';
    $db   = getenv('DB_NAME') ?: 'StudenthubDB';
    $port = getenv('DB_PORT') ?: 3306;

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8";

    try {
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    } catch (PDOException $e) {
        error_log("PDO connection failed: " . $e->getMessage());
        die("Connection failed: " . $e->getMessage());
    }
}
