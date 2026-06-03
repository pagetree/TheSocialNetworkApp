<?php

declare(strict_types=1);

/**
 * @return array{
 *     account_id: string,
 *     access_key: string,
 *     secret_key: string,
 *     endpoint: string,
 *     bucket: string,
 *     public_url: string,
 *     upload_prefix: string,
 *     region: string
 * }
 */
function r2Config(): array
{
    return [
        'account_id' => trim((string) (getenv('R2_ACCOUNT_ID') ?: '')),
        'access_key' => trim((string) (getenv('R2_ACCESS_KEY_ID') ?: '')),
        'secret_key' => trim((string) (getenv('R2_SECRET_ACCESS_KEY') ?: '')),
        'endpoint' => rtrim(str_replace('\\', '/', (string) (getenv('R2_ENDPOINT') ?: '')), '/'),
        'bucket' => trim((string) (getenv('R2_BUCKET') ?: '')),
        'public_url' => rtrim(str_replace('\\', '/', (string) (getenv('R2_PUBLIC_URL') ?: '')), '/'),
        'upload_prefix' => trim((string) (getenv('R2_UPLOAD_PREFIX') ?: 'user-uploads'), '/'),
        'region' => trim((string) (getenv('R2_REGION') ?: 'auto')),
    ];
}

function r2IsConfigured(): bool
{
    $config = r2Config();

    return $config['access_key'] !== ''
        && $config['secret_key'] !== ''
        && $config['endpoint'] !== ''
        && $config['bucket'] !== ''
        && $config['public_url'] !== '';
}

function r2PublicUrlForKey(string $objectKey): string
{
    $objectKey = ltrim(str_replace('\\', '/', $objectKey), '/');

    return r2Config()['public_url'] . '/' . $objectKey;
}

function r2BuildUploadFilename(int $userId, string $originalFilename): string
{
    $uploadDate = (new DateTimeImmutable('now'))->format('Y-m-d');
    $uniqueId = bin2hex(random_bytes(8));
    $original = basename(str_replace('\\', '/', $originalFilename));
    $original = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $original) ?? 'upload';
    $original = trim($original, '.-');

    if ($original === '') {
        $original = 'upload.bin';
    }

    if (strlen($original) > 120) {
        $extension = pathinfo($original, PATHINFO_EXTENSION);
        $stem = pathinfo($original, PATHINFO_FILENAME);
        $stem = substr((string) $stem, 0, 100);
        $original = $extension !== '' ? $stem . '.' . $extension : $stem;
    }

    return $userId . '.' . $uploadDate . '.' . $uniqueId . '.' . $original;
}

function r2BuildObjectKey(int $userId, string $originalFilename): string
{
    $config = r2Config();

    return $config['upload_prefix'] . '/' . r2BuildUploadFilename($userId, $originalFilename);
}

function r2PostMediaPrefix(): string
{
    return trim((string) (getenv('R2_POST_MEDIA_PREFIX') ?: 'posts-media'), '/');
}

function r2ReplyMediaPrefix(): string
{
    return trim((string) (getenv('R2_REPLY_MEDIA_PREFIX') ?: 'replies-media'), '/');
}

function r2BuildReplyMediaFilename(int $userId, int $conversationId, int $replyId, string $originalFilename): string
{
    $uploadDate = (new DateTimeImmutable('now'))->format('Y-m-d');
    $uniqueId = bin2hex(random_bytes(8));
    $original = r2SanitizeOriginalFilename($originalFilename);

    return $userId . '.' . $conversationId . '.' . $replyId . '.' . $uploadDate . '.' . $uniqueId . '.' . $original;
}

function r2BuildReplyMediaObjectKey(int $userId, int $conversationId, int $replyId, string $originalFilename): string
{
    return r2ReplyMediaPrefix() . '/' . r2BuildReplyMediaFilename($userId, $conversationId, $replyId, $originalFilename);
}

function r2SanitizeOriginalFilename(string $originalFilename): string
{
    $original = basename(str_replace('\\', '/', $originalFilename));
    $original = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $original) ?? 'upload';
    $original = trim($original, '.-');

    if ($original === '') {
        return 'upload.bin';
    }

    if (strlen($original) > 120) {
        $extension = pathinfo($original, PATHINFO_EXTENSION);
        $stem = pathinfo($original, PATHINFO_FILENAME);
        $stem = substr((string) $stem, 0, 100);
        $original = $extension !== '' ? $stem . '.' . $extension : $stem;
    }

    return $original;
}

function r2BuildPostMediaFilename(int $userId, int $postId, string $originalFilename): string
{
    $uploadDate = (new DateTimeImmutable('now'))->format('Y-m-d');
    $uniqueId = bin2hex(random_bytes(8));
    $original = r2SanitizeOriginalFilename($originalFilename);

    return $userId . '.' . $postId . '.' . $uploadDate . '.' . $uniqueId . '.' . $original;
}

function r2BuildPostMediaObjectKey(int $userId, int $postId, string $originalFilename): string
{
    return r2PostMediaPrefix() . '/' . r2BuildPostMediaFilename($userId, $postId, $originalFilename);
}

function r2MimeTypeForExtension(string $extension): string
{
    return match (strtolower($extension)) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        default => 'application/octet-stream',
    };
}

function r2EncodeUriPath(string $path): string
{
    $segments = explode('/', trim(str_replace('\\', '/', $path), '/'));

    return '/' . implode('/', array_map('rawurlencode', $segments));
}

function r2DeleteObjectByUrl(?string $publicUrl): void
{
    if ($publicUrl === null || $publicUrl === '' || !r2IsConfigured()) {
        return;
    }

    $prefix = r2Config()['public_url'] . '/';

    if (!str_starts_with($publicUrl, $prefix)) {
        return;
    }

    $objectKey = ltrim(substr($publicUrl, strlen($prefix)), '/');
    if ($objectKey === '') {
        return;
    }

    try {
        r2DeleteObject($objectKey);
    } catch (Throwable) {
        // Best effort cleanup only.
    }
}

function r2DeleteObject(string $objectKey): void
{
    $config = r2Config();
    $objectKey = ltrim(str_replace('\\', '/', $objectKey), '/');
    $canonicalUri = r2EncodeUriPath($config['bucket'] . '/' . $objectKey);

    r2SignedRequest('DELETE', $canonicalUri, '', 'application/octet-stream');
}

/**
 * @return array{ok: true, url: string, key: string}|array{ok: false, error: string}
 */
function r2UploadUserFile(int $userId, string $fieldName): array
{
    if (!r2IsConfigured()) {
        return ['ok' => false, 'error' => 'File storage is not configured.'];
    }

    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return ['ok' => false, 'error' => 'No file uploaded.'];
    }

    $file = $_FILES[$fieldName];
    $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'No file uploaded.'];
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Image upload failed.'];
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return ['ok' => false, 'error' => 'Invalid upload.'];
    }

    $originalFilename = (string) ($file['name'] ?? 'upload.bin');
    $objectKey = r2BuildObjectKey($userId, $originalFilename);
    $body = file_get_contents($tmpPath);

    if ($body === false) {
        return ['ok' => false, 'error' => 'Unable to read uploaded file.'];
    }

    $extension = strtolower(pathinfo($objectKey, PATHINFO_EXTENSION));
    $contentType = r2MimeTypeForExtension($extension);
    $config = r2Config();
    $canonicalUri = r2EncodeUriPath($config['bucket'] . '/' . $objectKey);

    try {
        r2SignedRequest('PUT', $canonicalUri, $body, $contentType);
    } catch (Throwable) {
        return ['ok' => false, 'error' => 'Unable to upload image right now.'];
    }

    return [
        'ok' => true,
        'url' => r2PublicUrlForKey($objectKey),
        'key' => $objectKey,
    ];
}

/**
 * @param array{
 *     media_type: string,
 *     content_type: string,
 *     tmp_path: string,
 *     original_filename: string
 * } $validatedMedia
 * @return array{ok: true, url: string, key: string, media_type: string}|array{ok: false, error: string}
 */
function r2UploadPostMediaFile(int $userId, int $postId, array $validatedMedia): array
{
    if (!r2IsConfigured()) {
        return ['ok' => false, 'error' => 'File storage is not configured.'];
    }

    $objectKey = r2BuildPostMediaObjectKey($userId, $postId, $validatedMedia['original_filename']);
    $body = file_get_contents($validatedMedia['tmp_path']);

    if ($body === false) {
        return ['ok' => false, 'error' => 'Unable to read uploaded file.'];
    }

    $config = r2Config();
    $canonicalUri = r2EncodeUriPath($config['bucket'] . '/' . $objectKey);

    try {
        r2SignedRequest('PUT', $canonicalUri, $body, $validatedMedia['content_type']);
    } catch (Throwable) {
        return ['ok' => false, 'error' => 'Unable to upload media right now.'];
    }

    return [
        'ok' => true,
        'url' => r2PublicUrlForKey($objectKey),
        'key' => $objectKey,
        'media_type' => $validatedMedia['media_type'],
    ];
}

/**
 * @return array{
 *     ok: true,
 *     url: string,
 *     key: string,
 *     media_type: string,
 *     validated: array<string, mixed>
 * }|array{ok: false, error: string}
 */
function r2UploadPostMedia(int $userId, int $postId, string $fieldName): array
{
    $validated = validatePostMediaUpload($fieldName);
    if (!$validated['ok']) {
        return $validated;
    }

    $upload = r2UploadPostMediaFile($userId, $postId, $validated);
    if (!$upload['ok']) {
        return $upload;
    }

    return [
        'ok' => true,
        'url' => $upload['url'],
        'key' => $upload['key'],
        'media_type' => $upload['media_type'],
        'validated' => $validated,
    ];
}

/**
 * @param array{
 *     media_type: string,
 *     content_type: string,
 *     tmp_path: string,
 *     original_filename: string
 * } $validatedMedia
 * @return array{ok: true, url: string, key: string, media_type: string}|array{ok: false, error: string}
 */
function r2UploadPostReplyMediaFile(int $userId, int $conversationId, int $replyId, array $validatedMedia): array
{
    if (!r2IsConfigured()) {
        return ['ok' => false, 'error' => 'File storage is not configured.'];
    }

    $objectKey = r2BuildReplyMediaObjectKey($userId, $conversationId, $replyId, $validatedMedia['original_filename']);
    $body = file_get_contents($validatedMedia['tmp_path']);

    if ($body === false) {
        return ['ok' => false, 'error' => 'Unable to read uploaded file.'];
    }

    $config = r2Config();
    $canonicalUri = r2EncodeUriPath($config['bucket'] . '/' . $objectKey);

    try {
        r2SignedRequest('PUT', $canonicalUri, $body, $validatedMedia['content_type']);
    } catch (Throwable) {
        return ['ok' => false, 'error' => 'Unable to upload media right now.'];
    }

    return [
        'ok' => true,
        'url' => r2PublicUrlForKey($objectKey),
        'key' => $objectKey,
        'media_type' => $validatedMedia['media_type'],
    ];
}

function r2SignedRequest(
    string $method,
    string $canonicalUri,
    string $body,
    string $contentType
): void {
    $config = r2Config();
    $service = 's3';
    $region = $config['region'];
    $endpoint = parse_url($config['endpoint']);

    if ($endpoint === false || !isset($endpoint['host'])) {
        throw new RuntimeException('Invalid R2 endpoint.');
    }

    $host = $endpoint['host'];
    $amzDate = gmdate('Ymd\THis\Z');
    $dateStamp = gmdate('Ymd');
    $payloadHash = hash('sha256', $body);

    $canonicalHeaders = 'host:' . $host . "\n"
        . 'x-amz-content-sha256:' . $payloadHash . "\n"
        . 'x-amz-date:' . $amzDate . "\n";

    if ($method === 'PUT') {
        $canonicalHeaders = 'content-type:' . $contentType . "\n" . $canonicalHeaders;
        $signedHeaders = 'content-type;host;x-amz-content-sha256;x-amz-date';
    } else {
        $signedHeaders = 'host;x-amz-content-sha256;x-amz-date';
    }

    $canonicalRequest = implode("\n", [
        $method,
        $canonicalUri,
        '',
        $canonicalHeaders,
        $signedHeaders,
        $payloadHash,
    ]);

    $credentialScope = $dateStamp . '/' . $region . '/' . $service . '/aws4_request';
    $stringToSign = implode("\n", [
        'AWS4-HMAC-SHA256',
        $amzDate,
        $credentialScope,
        hash('sha256', $canonicalRequest),
    ]);

    $signingKey = r2GetSignatureKey($config['secret_key'], $dateStamp, $region, $service);
    $signature = hash_hmac('sha256', $stringToSign, $signingKey);

    $authorization = 'AWS4-HMAC-SHA256 Credential=' . $config['access_key'] . '/' . $credentialScope
        . ', SignedHeaders=' . $signedHeaders
        . ', Signature=' . $signature;

    $url = $config['endpoint'] . $canonicalUri;
    $headers = [
        'Authorization: ' . $authorization,
        'x-amz-content-sha256: ' . $payloadHash,
        'x-amz-date: ' . $amzDate,
        'host: ' . $host,
    ];

    if ($method === 'PUT') {
        $headers[] = 'Content-Type: ' . $contentType;
        $headers[] = 'Content-Length: ' . strlen($body);
    }

    $curl = curl_init($url);
    if ($curl === false) {
        throw new RuntimeException('Unable to initialize upload request.');
    }

    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_POSTFIELDS => $method === 'PUT' ? $body : null,
    ]);

    $response = curl_exec($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($response === false || ($statusCode !== 200 && $statusCode !== 204)) {
        throw new RuntimeException('R2 request failed with status ' . $statusCode);
    }
}

function r2GetSignatureKey(string $secretKey, string $dateStamp, string $region, string $service): string
{
    $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $secretKey, true);
    $kRegion = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', $service, $kRegion, true);

    return hash_hmac('sha256', 'aws4_request', $kService, true);
}
