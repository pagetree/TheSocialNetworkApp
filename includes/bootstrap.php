<?php

declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/env.php';
loadAppEnv();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/profile.php';
require_once __DIR__ . '/r2-storage.php';

function jsonResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
}
