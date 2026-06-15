<?php
/**
 * Database installer for Dokploy / fresh deployments.
 *
 * Reads base de donne/BD.sql and executes every statement against
 * the database defined in includes/db_config.php.
 *
 * Usage: visit  your-domain.com/setup.php?key=install
 * After tables are created, DELETE this file from the server.
 */

// Simple one-time protection
$secret = 'install';
if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Database Setup</title></head>
    <body style="font-family:sans-serif;max-width:600px;margin:3rem auto;padding:1rem;text-align:center;">
        <h1>Database Setup</h1>
        <p>Click the button below to create all tables in your Dokploy database.</p>
        <a href="?key=install"
           style="display:inline-block;padding:1rem 2rem;background:#1a2332;color:#fff;text-decoration:none;border-radius:8px;font-weight:bold;">
            Run Installer
        </a>
        <p style="margin-top:2rem;color:#888;font-size:0.85rem;">
            After the tables are created, <b>delete this file</b> from the server.
        </p>
    </body>
    </html>
    <?php
    exit;
}

// Increase limits in case the SQL file is large
set_time_limit(120);
ini_set('memory_limit', '256M');

require_once __DIR__ . '/includes/db_config.php';

$conn = get_db_connection();

$sqlFile = __DIR__ . '/base de donne/BD.sql';

if (!file_exists($sqlFile)) {
    die(<<<HTML
<!DOCTYPE html>
<html><body style="font-family:sans-serif;padding:2rem;">
    <h2 style="color:#c00;">Error</h2>
    <p>SQL file not found: <code>{$sqlFile}</code></p>
</body></html>
HTML
    );
}

// Read raw SQL
$rawSql = file_get_contents($sqlFile);

// Split into individual statements by semicolons, preserving blocks
$statements = array_filter(
    array_map('trim', explode(';', $rawSql))
);

$executed = 0;
$skipped  = 0;
$errors   = [];

echo "<!DOCTYPE html>\n<html><head><title>Installing...</title></head>\n";
echo "<body style='font-family:sans-serif;max-width:800px;margin:2rem auto;padding:1rem;'>\n";
echo "<h2>Database Setup</h2>\n<pre style='background:#f5f5f5;padding:1rem;border-radius:8px;'>\n";

foreach ($statements as $sql) {
    // Skip pure comments / empty lines
    $check = preg_replace('|--.*?\n|s', '', $sql);
    $check = preg_replace('|/\*.*?\*/|s', '', $check);
    $check = trim($check);

    if ($check === '') {
        continue;
    }

    // Re-append semicolon for execution
    $sql .= ';';

    if ($conn->multi_query($sql)) {
        // Flush results (needed for multi_query)
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());

        $executed++;
        echo "OK  → " . substr(str_replace(["\n", "\r"], ' ', $check), 0, 80) . "\n";
    } else {
        $err = $conn->error;
        // "Table already exists" and "Duplicate column name" are safe to ignore
        if (strpos($err, 'already exists') !== false || strpos($err, 'Duplicate column') !== false) {
            $skipped++;
            echo "SKIP → " . substr(str_replace(["\n", "\r"], ' ', $check), 0, 80) . " (already there)\n";
        } else {
            $errors[] = $err;
            echo "ERR → " . $err . "\n";
        }
    }
}

echo "</pre>\n";

if (empty($errors)) {
    echo "<div style='background:#d4edda;color:#155724;padding:1rem;border-radius:8px;margin-bottom:1rem;'>\n";
    echo "<b>Success!</b> <br>Executed: $executed statements <br>Skipped (already present): $skipped</div>\n";
    echo "<p style='color:#c00;font-weight:bold;'>⚠️ DELETE setup.php from your server now! ⚠️</p>\n";
} else {
    echo "<div style='background:#f8d7da;color:#721c24;padding:1rem;border-radius:8px;margin-bottom:1rem;'>\n";
    echo "<b>Finished with errors.</b> Check the output above.</div>\n";
}

echo "</body>\n</html>";

$conn->close();
