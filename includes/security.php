<?php

declare(strict_types=1);

use Beeyev\DisposableEmailFilter\DisposableEmailFilter;
use Wikimedia\CommonPasswords\CommonPasswords;

/**
 * @return array{max: int, window_seconds: int}
 */
function rateLimitConfig(string $action): array
{
    return match ($action) {
        'auth.register' => ['max' => 5, 'window_seconds' => 900],
        'auth.login' => ['max' => 10, 'window_seconds' => 900],
        'auth.check_username' => ['max' => 60, 'window_seconds' => 900],
        'profile.update' => ['max' => 15, 'window_seconds' => 900],
        'posts.create' => ['max' => 20, 'window_seconds' => 900],
        'posts.stats' => ['max' => 120, 'window_seconds' => 900],
        'posts.reply' => ['max' => 30, 'window_seconds' => 900],
        'posts.like' => ['max' => 60, 'window_seconds' => 900],
        'users.follow' => ['max' => 60, 'window_seconds' => 900],
        default => ['max' => 30, 'window_seconds' => 60],
    };
}

function clientIpAddress(): string
{
    $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($forwarded !== '') {
        $parts = array_map('trim', explode(',', $forwarded));
        $first = $parts[0] ?? '';
        if (filter_var($first, FILTER_VALIDATE_IP)) {
            return $first;
        }
    }

    $remote = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '0.0.0.0';
}

function hashAppPassword(string $password): string
{
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
        'time_cost' => PASSWORD_ARGON2_DEFAULT_TIME_COST,
        'threads' => PASSWORD_ARGON2_DEFAULT_THREADS,
    ]);
}

function verifyAppPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

function passwordNeedsRehash(string $hash): bool
{
    return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
        'memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
        'time_cost' => PASSWORD_ARGON2_DEFAULT_TIME_COST,
        'threads' => PASSWORD_ARGON2_DEFAULT_THREADS,
    ]);
}

function disposableEmailFilter(): DisposableEmailFilter
{
    static $filter = null;

    if ($filter === null) {
        $filter = new DisposableEmailFilter();
    }

    return $filter;
}

function isDisposableEmail(string $email): bool
{
    $filter = disposableEmailFilter();

    if (!$filter->isEmailAddressValid($email)) {
        return false;
    }

    return $filter->isDisposableEmailAddress($email);
}

function isCommonPassword(string $password): bool
{
    if ($password === '') {
        return false;
    }

    return CommonPasswords::isCommon($password) || CommonPasswords::isCommon(strtolower($password));
}

function validateRegistrationEmail(string $email): ?string
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Enter a valid email address.';
    }

    if (isDisposableEmail($email)) {
        return 'Disposable email addresses are not allowed.';
    }

    return null;
}

function validateRegistrationPassword(string $password, string $username, string $email): ?string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }

    if (strlen($password) > 128) {
        return 'Password must be 128 characters or less.';
    }

    if (isCommonPassword($password)) {
        return 'Choose a stronger password that is not commonly used.';
    }

    $username = normalizeUsername($username);
    $emailLocal = strtolower(strtok($email, '@') ?: '');

    if ($username !== '' && stripos($password, $username) !== false) {
        return 'Password must not contain your username.';
    }

    if ($emailLocal !== '' && stripos($password, $emailLocal) !== false) {
        return 'Password must not contain your email address.';
    }

    return null;
}

function purgeExpiredSecurityRows(): void
{
    $pdo = createPdoConnection();
    $pdo->exec('DELETE FROM rate_limit_buckets WHERE window_expires_at < NOW()');
    $pdo->exec('DELETE FROM csrf_tokens WHERE expires_at < NOW()');
}

function enforceRateLimit(string $action): ?array
{
    purgeExpiredSecurityRows();

    $config = rateLimitConfig($action);
    $ip = clientIpAddress();
    $bucketKey = $action . ':' . $ip;
    $pdo = createPdoConnection();

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'SELECT hit_count, window_expires_at
             FROM rate_limit_buckets
             WHERE bucket_key = :bucket_key
             FOR UPDATE'
        );
        $stmt->execute(['bucket_key' => $bucketKey]);
        $row = $stmt->fetch();

        if ($row === false || strtotime((string) $row['window_expires_at']) <= time()) {
            $expiresAt = (new DateTimeImmutable('now'))
                ->modify('+' . $config['window_seconds'] . ' seconds')
                ->format('Y-m-d H:i:sP');

            $upsert = $pdo->prepare(
                'INSERT INTO rate_limit_buckets (bucket_key, action, ip_address, hit_count, window_start, window_expires_at)
                 VALUES (:bucket_key, :action, :ip_address, 1, NOW(), :window_expires_at)
                 ON CONFLICT (bucket_key) DO UPDATE SET
                    action = EXCLUDED.action,
                    ip_address = EXCLUDED.ip_address,
                    hit_count = 1,
                    window_start = NOW(),
                    window_expires_at = EXCLUDED.window_expires_at'
            );
            $upsert->execute([
                'bucket_key' => $bucketKey,
                'action' => $action,
                'ip_address' => $ip,
                'window_expires_at' => $expiresAt,
            ]);
            $pdo->commit();

            return null;
        }

        if ((int) $row['hit_count'] >= $config['max']) {
            $pdo->commit();

            logSecurityEvent('rate_limit', [
                'action' => $action,
                'ip' => $ip,
            ]);

            return [
                'status' => 429,
                'error' => 'Too many attempts. Try again later.',
            ];
        }

        $update = $pdo->prepare(
            'UPDATE rate_limit_buckets
             SET hit_count = hit_count + 1
             WHERE bucket_key = :bucket_key'
        );
        $update->execute(['bucket_key' => $bucketKey]);
        $pdo->commit();

        return null;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
}

function createCsrfToken(string $purpose): string
{
    startAppSession();
    purgeExpiredSecurityRows();

    $sessionId = session_id();
    if ($sessionId === '') {
        throw new RuntimeException('Session could not be started.');
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = (new DateTimeImmutable('now'))
        ->modify('+1 hour')
        ->format('Y-m-d H:i:sP');

    $pdo = createPdoConnection();
    $pdo->prepare(
        'DELETE FROM csrf_tokens
         WHERE session_id = :session_id AND purpose = :purpose AND used_at IS NULL'
    )->execute([
        'session_id' => $sessionId,
        'purpose' => $purpose,
    ]);

    $insert = $pdo->prepare(
        'INSERT INTO csrf_tokens (token_hash, session_id, purpose, expires_at)
         VALUES (:token_hash, :session_id, :purpose, :expires_at)'
    );
    $insert->execute([
        'token_hash' => $tokenHash,
        'session_id' => $sessionId,
        'purpose' => $purpose,
        'expires_at' => $expiresAt,
    ]);

    return $token;
}

function validateCsrfToken(string $token, string $purpose): bool
{
    if ($token === '') {
        return false;
    }

    startAppSession();
    $sessionId = session_id();
    if ($sessionId === '') {
        return false;
    }

    $tokenHash = hash('sha256', $token);
    $pdo = createPdoConnection();

    $stmt = $pdo->prepare(
        'SELECT id
         FROM csrf_tokens
         WHERE token_hash = :token_hash
           AND session_id = :session_id
           AND purpose = :purpose
           AND expires_at > NOW()
           AND used_at IS NULL
         LIMIT 1'
    );
    $stmt->execute([
        'token_hash' => $tokenHash,
        'session_id' => $sessionId,
        'purpose' => $purpose,
    ]);
    $row = $stmt->fetch();

    if ($row === false) {
        return false;
    }

    return true;
}

function invalidateCsrfTokens(string $purpose): void
{
    startAppSession();
    $sessionId = session_id();
    if ($sessionId === '') {
        return;
    }

    $pdo = createPdoConnection();
    $pdo->prepare(
        'DELETE FROM csrf_tokens WHERE session_id = :session_id AND purpose = :purpose'
    )->execute([
        'session_id' => $sessionId,
        'purpose' => $purpose,
    ]);
}

function extractCsrfToken(array $payload): string
{
    $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (is_string($header) && $header !== '') {
        return trim($header);
    }

    return trim((string) ($payload['csrf_token'] ?? ''));
}

function isHoneypotTripped(array $payload): bool
{
    $value = trim((string) ($payload['_hp_url'] ?? ''));

    return $value !== '';
}

function logSecurityEvent(string $eventType, array $metadata = []): void
{
    startAppSession();

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO security_events (event_type, ip_address, session_id, metadata)
         VALUES (:event_type, :ip_address, :session_id, :metadata)'
    );
    $stmt->execute([
        'event_type' => $eventType,
        'ip_address' => clientIpAddress(),
        'session_id' => session_id() ?: null,
        'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
    ]);
}

/**
 * @return array{status: int, error: string}|null
 */
function guardAuthRequest(string $action, string $csrfPurpose, array $payload): ?array
{
    $rateLimitError = enforceRateLimit($action);
    if ($rateLimitError !== null) {
        return $rateLimitError;
    }

    if (isHoneypotTripped($payload)) {
        logSecurityEvent('honeypot', ['action' => $action]);

        return [
            'status' => 400,
            'error' => 'Unable to process request.',
        ];
    }

    if (!validateCsrfToken(extractCsrfToken($payload), $csrfPurpose)) {
        logSecurityEvent('csrf_invalid', ['action' => $action]);

        return [
            'status' => 403,
            'error' => 'Session expired. Refresh and try again.',
        ];
    }

    return null;
}

function authPayloadFromRequest(): array
{
    $payload = json_decode(file_get_contents('php://input') ?: '', true);

    return is_array($payload) ? $payload : $_POST;
}
