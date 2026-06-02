<?php

declare(strict_types=1);

function startAppSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);
    }
}

function getCurrentUser(): ?array
{
    startAppSession();

    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
        return null;
    }

    return $_SESSION['user'];
}

function isLoggedIn(): bool
{
    return getCurrentUser() !== null;
}

function loginUser(array $user): void
{
    startAppSession();
    unset($user['password_hash']);
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
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT id, username, display_name, handle, email, password_hash, avatar_url, bio, location
         FROM users
         WHERE email = :identifier OR username = :identifier
         LIMIT 1'
    );
    $stmt->execute(['identifier' => $identifier]);
    $user = $stmt->fetch();

    if ($user === false || !password_verify($password, $user['password_hash'])) {
        return null;
    }

    return $user;
}

function normalizeUsername(string $username): string
{
    return strtolower(preg_replace('/[^a-z0-9_]/', '', strtolower(trim($username))) ?? '');
}

function registerUser(
    string $firstName,
    string $lastName,
    string $username,
    string $email,
    string $password
): array {
    $pdo = createPdoConnection();
    $username = normalizeUsername($username);
    $email = strtolower(trim($email));
    $handle = '@' . $username;
    $displayName = trim($firstName . ' ' . $lastName);
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO users (username, display_name, handle, email, password_hash)
         VALUES (:username, :display_name, :handle, :email, :password_hash)
         RETURNING id, username, display_name, handle, email, avatar_url, bio, location'
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
