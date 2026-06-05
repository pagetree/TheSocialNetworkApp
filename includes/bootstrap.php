<?php

declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/env.php';
loadAppEnv();
initAppLocale();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/security.php';
sendSecurityHeaders();
require_once __DIR__ . '/theme.php';
require_once __DIR__ . '/profile.php';
require_once __DIR__ . '/posts.php';
require_once __DIR__ . '/post-media.php';
require_once __DIR__ . '/image-compress.php';
require_once __DIR__ . '/post-stats.php';
require_once __DIR__ . '/post-replies.php';
require_once __DIR__ . '/post-likes.php';
require_once __DIR__ . '/post-reposts.php';
require_once __DIR__ . '/user-follows.php';
require_once __DIR__ . '/onboarding.php';
require_once __DIR__ . '/post-participants.php';
require_once __DIR__ . '/post-scores.php';
require_once __DIR__ . '/r2-storage.php';
require_once __DIR__ . '/hashtags.php';
require_once __DIR__ . '/welcome-stats.php';
require_once __DIR__ . '/post-remove.php';
require_once __DIR__ . '/content-reports.php';
require_once __DIR__ . '/seo.php';

function jsonResponse(array $payload, int $statusCode = 200): void
{
    if (isset($payload['error']) && is_string($payload['error'])) {
        $payload['error'] = translateUserMessage($payload['error']);
    }

    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
}
