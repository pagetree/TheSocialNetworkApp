<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run from CLI: php sql/migrate.php\n");
    exit(1);
}

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$migrationsDir = __DIR__ . '/migrations';

if (!is_dir($migrationsDir)) {
    fwrite(STDERR, "Migrations directory not found: {$migrationsDir}\n");
    exit(1);
}

$config = databaseConfig();
$usingDatabaseUrl = is_string(getenv('DATABASE_URL')) && getenv('DATABASE_URL') !== '';
echo 'Connecting to ' . $config['driver'] . '://' . $config['host'] . ':' . $config['port'] . '/' . $config['database'];
echo $usingDatabaseUrl ? " (DATABASE_URL)\n" : " (PG* fallback)\n";

$pdo = createPdoConnection();

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        id SERIAL PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        applied_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
    )'
);

$applied = $pdo->query('SELECT migration FROM schema_migrations ORDER BY migration')
    ->fetchAll(PDO::FETCH_COLUMN);

$appliedMap = array_fill_keys($applied, true);

$files = glob($migrationsDir . '/*.sql') ?: [];
sort($files, SORT_STRING);

if ($files === []) {
    echo "No migration files found.\n";
    exit(0);
}

$ran = 0;

foreach ($files as $file) {
    $name = basename($file);

    if (isset($appliedMap[$name])) {
        echo "Skip: {$name}\n";
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        fwrite(STDERR, "Failed to read migration: {$name}\n");
        exit(1);
    }

    echo "Run: {$name}\n";

    try {
        $pdo->beginTransaction();
        $pdo->exec($sql);
        $stmt = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:migration)');
        $stmt->execute(['migration' => $name]);
        $pdo->commit();
        $ran++;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        fwrite(STDERR, "Migration failed ({$name}): {$exception->getMessage()}\n");
        exit(1);
    }
}

echo $ran > 0 ? "Done. {$ran} migration(s) applied.\n" : "Nothing to migrate.\n";
