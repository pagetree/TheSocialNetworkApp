<?php

declare(strict_types=1);

/**
 * Web path prefix for this app (empty string when served from domain root).
 */
function appBasePath(): string
{
    $override = getenv('APP_BASE_PATH');
    if (is_string($override) && $override !== '') {
        $normalized = str_replace('\\', '/', trim($override));

        if ($normalized === '/') {
            return '';
        }

        return rtrim($normalized, '/');
    }

    $projectRoot = str_replace('\\', '/', realpath(dirname(__DIR__)) ?: dirname(__DIR__));
    $documentRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');

    if ($documentRoot !== '' && str_starts_with($projectRoot, $documentRoot)) {
        $relative = substr($projectRoot, strlen($documentRoot));

        if ($relative === '' || $relative === '/') {
            return '';
        }

        return rtrim($relative, '/');
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');

    if (str_ends_with($scriptName, '/router.php') || str_ends_with($scriptName, 'router.php')) {
        return '';
    }

    $scriptDir = dirname($scriptName);

    if ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '\\') {
        return '';
    }

    return rtrim($scriptDir, '/');
}

function normalizeAppPath(string $appPath): string
{
    if ($appPath !== '/' && str_ends_with($appPath, '/')) {
        $appPath = rtrim($appPath, '/') ?: '/';
    }

    return $appPath;
}

/**
 * App-relative path for the current request (e.g. /profile).
 */
function appRequestPath(): string
{
    static $appPath = null;

    if ($appPath !== null) {
        return $appPath;
    }

    $basePath = appBasePath();
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    if ($basePath !== '' && str_starts_with($requestPath, $basePath)) {
        $appPath = substr($requestPath, strlen($basePath)) ?: '/';
    } else {
        $appPath = $requestPath;
    }

    if ($appPath === '' || !str_starts_with($appPath, '/')) {
        $appPath = $appPath === '' ? '/' : '/' . ltrim($appPath, '/');
    }

    $appPath = normalizeAppPath($appPath);

    return $appPath;
}

/**
 * @return array{base: string, path: string, url: callable(string): string}
 */
function appPaths(): array
{
    static $paths = null;

    if ($paths !== null) {
        return $paths;
    }

    $basePath = appBasePath();
    $appPath = appRequestPath();

    $url = static function (string $uri) use ($basePath): string {
        if ($uri === '' || $uri === '/') {
            return ($basePath === '' ? '' : $basePath) . '/';
        }

        $path = $uri[0] === '/' ? $uri : '/' . $uri;

        return $basePath . $path;
    };

    $paths = [
        'base' => $basePath,
        'path' => $appPath,
        'url' => $url,
    ];

    return $paths;
}

/**
 * Absolute URL helper when APP_URL is configured (optional).
 */
function appUrl(string $uri = '/'): string
{
    $appUrl = getenv('APP_URL');
    $path = appPaths()['url']($uri);

    if (!is_string($appUrl) || $appUrl === '') {
        return $path;
    }

    return rtrim(str_replace('\\', '/', $appUrl), '/') . ($path === '/' ? '' : $path);
}
