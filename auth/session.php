<?php

declare(strict_types=1);

function appSessionCookiePath(): string
{
    $basePath = appBasePath();

    if ($basePath === '' || $basePath === '/') {
        return '/';
    }

    return rtrim($basePath, '/') . '/';
}

function configureAppSessionCookie(): void
{
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => appSessionCookiePath(),
        'secure' => isHttpsRequest(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function startAppSession(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    configureAppSessionCookie();

    if (databaseSessionStorageAvailable()) {
        session_set_save_handler(new DatabaseSessionHandler(createPdoConnection()), true);
    }

    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => isHttpsRequest(),
        'cookie_path' => appSessionCookiePath(),
    ]);
}

function getCurrentUser(): ?array
{
    startAppSession();

    if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
        return $_SESSION['user'];
    }

    $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    if ($userId < 1) {
        return null;
    }

    $user = fetchUserById($userId);
    if ($user === null) {
        return null;
    }

    unset($user['password_hash'], $user['email']);
    $_SESSION['user'] = $user;

    return $user;
}

function isLoggedIn(): bool
{
    return getCurrentUser() !== null;
}

function loginUser(array $user): void
{
    startAppSession();
    session_regenerate_id(true);
    unset($user['password_hash'], $user['email']);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user'] = $user;
}

function logoutUser(): void
{
    startAppSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }
    session_destroy();
}

function attemptLogin(string $identifier, string $password): ?array
{
    $identifier = trim($identifier);
    if (str_contains($identifier, '@') && !str_starts_with($identifier, '@')) {
        $identifier = strtolower($identifier);
    } else {
        $identifier = prepareUsernameInput($identifier);
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT id, username, display_name, handle, email, password_hash, avatar_url, cover_url, bio, location, website_url, date_of_birth, created_at
         FROM users
         WHERE email = :identifier OR username = :identifier
         LIMIT 1'
    );
    $stmt->execute(['identifier' => $identifier]);
    $user = $stmt->fetch();

    if ($user === false || !verifyAppPassword($password, $user['password_hash'])) {
        return null;
    }

    if (passwordNeedsRehash($user['password_hash'])) {
        $pdo = createPdoConnection();
        $newHash = hashAppPassword($password);
        $update = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
        $update->execute([
            'password_hash' => $newHash,
            'id' => (int) $user['id'],
        ]);
    }

    return fetchUserById((int) $user['id']) ?? $user;
}

const USERNAME_MIN_LENGTH = 3;
const USERNAME_MAX_LENGTH = 50;

/** ASCII slug charset for @handles: letters, digits, single . - _ between alphanumerics. */
const USERNAME_FORMAT_PATTERN = '/^[a-z0-9](?:[a-z0-9]*(?:[._-][a-z0-9]+)*)?$/';

/**
 * @return list<string>
 */
function reservedUsernames(): array
{
    return [
        'admin',
        'administrator',
        'analytics',
        'api',
        'auth',
        'explore',
        'hashtag',
        'health',
        'help',
        'home',
        'login',
        'logout',
        'messages',
        'moderator',
        'notifications',
        'onboarding',
        'post',
        'posts',
        'profile',
        'register',
        'root',
        'search',
        'settings',
        'staff',
        'support',
        'system',
        'team',
        'www',
    ];
}

function prepareUsernameInput(string $username): string
{
    $username = strtolower(trim($username));

    return ltrim($username, '@');
}

function normalizeUsername(string $username): string
{
    return prepareUsernameInput($username);
}

function isReservedUsername(string $username): bool
{
    return in_array(prepareUsernameInput($username), reservedUsernames(), true);
}

function validateUsernameFormat(string $username): ?string
{
    $username = prepareUsernameInput($username);

    if ($username === '') {
        return 'Username is required.';
    }

    if (strlen($username) < USERNAME_MIN_LENGTH) {
        return 'Username must be at least 3 characters (letters, numbers, period, hyphen, underscore).';
    }

    if (strlen($username) > USERNAME_MAX_LENGTH) {
        return 'Username must be 50 characters or less.';
    }

    if (!preg_match('/^[a-z0-9._-]+$/', $username)) {
        return 'Username is not valid.';
    }

    if (
        str_contains($username, '..')
        || str_contains($username, '--')
        || str_contains($username, '__')
    ) {
        return 'Username is not valid.';
    }

    if (!preg_match(USERNAME_FORMAT_PATTERN, $username)) {
        return 'Username is not valid.';
    }

    if (isReservedUsername($username)) {
        return 'Username is not valid.';
    }

    return null;
}

/**
 * @return array{valid: bool, available: bool, username: string, error: ?string}
 */
function checkUsernameAvailability(string $username): array
{
    $normalized = normalizeUsername($username);
    $formatError = validateUsernameFormat($username);

    if ($formatError !== null) {
        return [
            'valid' => false,
            'available' => false,
            'username' => $normalized,
            'error' => $formatError,
        ];
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT 1 FROM users WHERE username = :username OR handle = :handle LIMIT 1'
    );
    $stmt->execute([
        'username' => $normalized,
        'handle' => '@' . $normalized,
    ]);

    if ($stmt->fetchColumn() !== false) {
        return [
            'valid' => true,
            'available' => false,
            'username' => $normalized,
            'error' => 'Username is already taken.',
        ];
    }

    return [
        'valid' => true,
        'available' => true,
        'username' => $normalized,
        'error' => null,
    ];
}

function registerUser(
    string $firstName,
    string $lastName,
    string $username,
    string $email,
    string $password
): array {
    $formatError = validateUsernameFormat($username);
    if ($formatError !== null) {
        throw new InvalidArgumentException($formatError);
    }

    $pdo = createPdoConnection();
    $username = normalizeUsername($username);
    $email = strtolower(trim($email));
    $handle = '@' . $username;
    $displayName = trim($firstName . ' ' . $lastName);
    $passwordHash = hashAppPassword($password);

    $stmt = $pdo->prepare(
        'INSERT INTO users (username, display_name, handle, email, password_hash)
         VALUES (:username, :display_name, :handle, :email, :password_hash)
         RETURNING id, username, display_name, handle, email, avatar_url, cover_url, bio, location, website_url, date_of_birth, is_visible, onboarding_completed_at, created_at'
    );
    $stmt->execute([
        'username' => $username,
        'display_name' => $displayName,
        'handle' => $handle,
        'email' => $email,
        'password_hash' => $passwordHash,
    ]);

    $user = $stmt->fetch();
    if ($user === false) {
        throw new RuntimeException('User could not be created.');
    }

    return $user;
}

function userExistsByEmailOrUsername(string $email, string $username): bool
{
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT 1 FROM users WHERE email = :email OR username = :username OR handle = :handle LIMIT 1'
    );
    $stmt->execute([
        'email' => strtolower(trim($email)),
        'username' => normalizeUsername($username),
        'handle' => '@' . normalizeUsername($username),
    ]);

    return $stmt->fetchColumn() !== false;
}

/**
 * @return array<string, mixed>|null
 */
function fetchUserById(int $userId): ?array
{
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT ' . userSessionSelectSql() . '
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    return $user === false ? null : $user;
}

/**
 * @return array<string, mixed>|null
 */
function fetchUserByUsername(string $username): ?array
{
    $normalized = normalizeUsername($username);
    if ($normalized === '' || validateUsernameFormat($normalized) !== null) {
        return null;
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT ' . userSessionSelectSql() . '
         FROM users
         WHERE username = :username
         LIMIT 1'
    );
    $stmt->execute(['username' => $normalized]);
    $user = $stmt->fetch();

    return $user === false ? null : $user;
}

/**
 * @param array{display_name: string, bio: ?string, location: ?string, website_url: ?string, date_of_birth: ?string, avatar_url: ?string, cover_url: ?string, is_visible: bool} $profile
 * @return array<string, mixed>|null
 */
function updateUserProfile(int $userId, array $profile): ?array
{
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'UPDATE users
         SET display_name = :display_name,
             bio = :bio,
             location = :location,
             website_url = :website_url,
             date_of_birth = :date_of_birth,
             avatar_url = :avatar_url,
             cover_url = :cover_url,
             is_visible = :is_visible,
             updated_at = NOW()
         WHERE id = :id
         RETURNING ' . userSessionSelectSql()
    );
    $stmt->execute([
        'id' => $userId,
        'display_name' => $profile['display_name'],
        'bio' => $profile['bio'],
        'location' => $profile['location'],
        'website_url' => $profile['website_url'],
        'date_of_birth' => $profile['date_of_birth'],
        'avatar_url' => $profile['avatar_url'],
        'cover_url' => $profile['cover_url'],
        'is_visible' => $profile['is_visible'],
    ]);
    $user = $stmt->fetch();

    return $user === false ? null : $user;
}

/**
 * @return array<string, mixed>
 */
function userProfilePayload(array $user, callable $url): array
{
    $websiteUrl = (string) ($user['website_url'] ?? '');
    $dateOfBirth = $user['date_of_birth'] ?? null;
    $location = (string) ($user['location'] ?? '');
    $bio = (string) ($user['bio'] ?? '');

    return [
        'id' => (int) $user['id'],
        'username' => (string) $user['username'],
        'handle' => (string) $user['handle'],
        'display_name' => (string) $user['display_name'],
        'bio' => $bio,
        'location' => $location,
        'website_url' => $websiteUrl,
        'website_label' => websiteDisplayLabel($websiteUrl),
        'date_of_birth' => is_string($dateOfBirth) ? $dateOfBirth : null,
        'date_of_birth_label' => formatProfileBirthdayLabel(is_string($dateOfBirth) ? $dateOfBirth : null),
        'joined_label' => formatProfileJoinedDate((string) ($user['created_at'] ?? '')),
        'avatar_url' => userMediaUrl($user, 'avatar_url', $url),
        'cover_url' => userMediaUrl($user, 'cover_url', $url),
        'is_visible' => userProfileIsVisible($user),
    ];
}
