<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($path === '/health') {
    jsonResponse([
        'status' => 'ok',
        'service' => 'TheSocialNetworkApp',
    ]);
    return;
}

if ($path === '/db-check') {
    try {
        $pdo = createPdoConnection();
        $result = $pdo->query('SELECT NOW() AS server_time')->fetch();
        jsonResponse([
            'status' => 'ok',
            'database' => 'connected',
            'server_time' => $result['server_time'] ?? null,
        ]);
    } catch (Throwable $exception) {
        jsonResponse([
            'status' => 'error',
            'database' => 'unreachable',
            'message' => $exception->getMessage(),
        ], 500);
    }
    return;
}

http_response_code(200);
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TheSocialNetworkApp</title>
</head>
<body>
    <h1>TheSocialNetworkApp scaffold is live</h1>
    <p>Use <code>/health</code> for app status and <code>/db-check</code> for PostgreSQL connectivity.</p>
</body>
</html>
