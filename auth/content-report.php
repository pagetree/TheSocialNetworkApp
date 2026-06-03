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

$payload = authPayloadFromRequest();
$guardError = guardAuthRequest('content.report', 'content_report', $payload);
if ($guardError !== null) {
    jsonResponse([
        'ok' => false,
        'error' => $guardError['error'],
    ], $guardError['status']);
    return;
}

$targetType = (string) ($payload['target_type'] ?? '');
$targetId = (int) ($payload['target_id'] ?? 0);
$reasonCode = (string) ($payload['reason_code'] ?? '');
$details = isset($payload['details']) ? (string) $payload['details'] : null;

try {
    $result = submitContentReport(
        (int) $sessionUser['id'],
        $targetType,
        $targetId,
        $reasonCode,
        $details
    );
} catch (Throwable) {
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to submit report right now.',
    ], 500);
    return;
}

if (!$result['ok']) {
    jsonResponse([
        'ok' => false,
        'error' => $result['error'],
    ], $result['status']);
    return;
}

consumeCsrfToken(extractCsrfToken($payload), 'content_report');

jsonResponse([
    'ok' => true,
    'report_id' => $result['report_id'],
]);
