<?php

declare(strict_types=1);

function databaseConfig(): array
{
    $databaseUrl = getenv('DATABASE_URL');
    if (is_string($databaseUrl) && $databaseUrl !== '') {
        $parts = parse_url($databaseUrl);
        if ($parts !== false) {
            return [
                'driver' => 'pgsql',
                'host' => $parts['host'] ?? '127.0.0.1',
                'port' => isset($parts['port']) ? (int) $parts['port'] : 5432,
                'database' => isset($parts['path']) ? ltrim($parts['path'], '/') : 'postgres',
                'user' => $parts['user'] ?? 'postgres',
                'password' => $parts['pass'] ?? '',
            ];
        }
    }

    return [
        'driver' => 'pgsql',
        'host' => getenv('PGHOST') ?: '127.0.0.1',
        'port' => (int) (getenv('PGPORT') ?: 5432),
        'database' => getenv('PGDATABASE') ?: 'postgres',
        'user' => getenv('PGUSER') ?: 'postgres',
        'password' => getenv('PGPASSWORD') ?: '',
    ];
}

function createPdoConnection(): PDO
{
    $config = databaseConfig();
    $dsn = sprintf(
        '%s:host=%s;port=%d;dbname=%s',
        $config['driver'],
        $config['host'],
        $config['port'],
        $config['database']
    );

    return new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
