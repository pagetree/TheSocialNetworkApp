<?php

declare(strict_types=1);

$sessionUser = getCurrentUser();
if ($sessionUser === null) {
    jsonResponse([
        'ok' => false,
        'error' => 'You must be signed in.',
    ], 401);
    return;
}

$period = (string) ($_GET['period'] ?? '7d');

try {
    $result = fetchUserPostImpressionsSeries((int) $sessionUser['id'], $period);
} catch (Throwable) {
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to load analytics right now.',
    ], 500);
    return;
}

if (!$result['ok']) {
    jsonResponse([
        'ok' => false,
        'error' => $result['error'],
    ], 422);
    return;
}

jsonResponse($result);
