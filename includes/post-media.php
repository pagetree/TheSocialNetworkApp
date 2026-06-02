<?php

declare(strict_types=1);

const POST_IMAGE_MAX_BYTES = 15_728_640; // 15 MB
const POST_VIDEO_MAX_BYTES = 52_428_800; // 50 MB
const POST_MAX_IMAGES = 4;
const POST_MAX_VIDEOS = 1;

/** @var list<string> */
const POST_IMAGE_EXTENSIONS = [
    'avif', 'bmp', 'gif', 'heic', 'heif', 'ico', 'jpeg', 'jpg', 'png', 'svg', 'tif', 'tiff', 'webp',
];

/** @var list<string> */
const POST_VIDEO_EXTENSIONS = [
    '3g2', '3gp', 'avi', 'm4v', 'mkv', 'mov', 'mp4', 'mpeg', 'mpg', 'ogv', 'webm',
];

/** @var list<string> */
const POST_IMAGE_MIME_TYPES = [
    'image/avif',
    'image/bmp',
    'image/gif',
    'image/heic',
    'image/heif',
    'image/jpeg',
    'image/png',
    'image/svg+xml',
    'image/tiff',
    'image/webp',
    'image/x-icon',
    'image/vnd.microsoft.icon',
];

/** @var list<string> */
const POST_VIDEO_MIME_TYPES = [
    'video/3gpp',
    'video/3gpp2',
    'video/avi',
    'video/mp4',
    'video/mpeg',
    'video/ogg',
    'video/quicktime',
    'video/webm',
    'video/x-matroska',
    'video/x-msvideo',
];

/**
 * @return list<array{name: string, type: string, tmp_name: string, error: int, size: int}>
 */
function postMediaNormalizeUploadField(string $fieldName): array
{
    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return [];
    }

    $file = $_FILES[$fieldName];
    if (!is_array($file['name'] ?? null)) {
        return [[
            'name' => (string) ($file['name'] ?? ''),
            'type' => (string) ($file['type'] ?? ''),
            'tmp_name' => (string) ($file['tmp_name'] ?? ''),
            'error' => (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int) ($file['size'] ?? 0),
        ]];
    }

    $entries = [];
    foreach ($file['name'] as $index => $name) {
        $entries[] = [
            'name' => (string) $name,
            'type' => (string) ($file['type'][$index] ?? ''),
            'tmp_name' => (string) ($file['tmp_name'][$index] ?? ''),
            'error' => (int) ($file['error'][$index] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int) ($file['size'][$index] ?? 0),
        ];
    }

    return $entries;
}

function postMediaHasUpload(string $fieldName): bool
{
    foreach (postMediaNormalizeUploadField($fieldName) as $file) {
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            return true;
        }
    }

    return false;
}

function postMediaExtensionFromFilename(string $filename): string
{
    $extension = strtolower(pathinfo(basename(str_replace('\\', '/', $filename)), PATHINFO_EXTENSION));

    return preg_replace('/[^a-z0-9]+/', '', $extension) ?? '';
}

function postMediaDetectMimeType(string $tmpPath): string
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        return '';
    }

    $mime = finfo_file($finfo, $tmpPath);
    finfo_close($finfo);

    return is_string($mime) ? strtolower(trim($mime)) : '';
}

/**
 * @return 'image'|'gif'|'video'|null
 */
function postMediaTypeForMimeAndExtension(string $mime, string $extension): ?string
{
    if ($extension === 'gif' || $mime === 'image/gif') {
        return 'gif';
    }

    if (in_array($extension, POST_VIDEO_EXTENSIONS, true) || in_array($mime, POST_VIDEO_MIME_TYPES, true)) {
        return 'video';
    }

    if (in_array($extension, POST_IMAGE_EXTENSIONS, true) || in_array($mime, POST_IMAGE_MIME_TYPES, true)) {
        return 'image';
    }

    return null;
}

function postMediaMimeAllowedForType(string $mediaType, string $mime, string $extension): bool
{
    if ($mediaType === 'video') {
        if (!in_array($extension, POST_VIDEO_EXTENSIONS, true)) {
            return false;
        }

        return in_array($mime, POST_VIDEO_MIME_TYPES, true) || $mime === 'application/octet-stream';
    }

    if ($mediaType === 'gif') {
        return $extension === 'gif'
            && ($mime === 'image/gif' || $mime === 'application/octet-stream');
    }

    if ($mediaType === 'image') {
        if (!in_array($extension, POST_IMAGE_EXTENSIONS, true) || $extension === 'gif') {
            return false;
        }

        if (in_array($extension, ['heic', 'heif'], true)) {
            return in_array($mime, ['image/heic', 'image/heif', 'application/octet-stream'], true);
        }

        return in_array($mime, POST_IMAGE_MIME_TYPES, true) || $mime === 'application/octet-stream';
    }

    return false;
}

function postMediaMaxBytesForType(string $mediaType): int
{
    return $mediaType === 'video' ? POST_VIDEO_MAX_BYTES : POST_IMAGE_MAX_BYTES;
}

function postMediaContentTypeForUpload(string $mediaType, string $mime, string $extension): string
{
    if ($mime !== '' && $mime !== 'application/octet-stream') {
        return $mime;
    }

    return match ($extension) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'bmp' => 'image/bmp',
        'tif', 'tiff' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'avif' => 'image/avif',
        'heic' => 'image/heic',
        'heif' => 'image/heif',
        'ico' => 'image/x-icon',
        'mp4', 'm4v' => 'video/mp4',
        'webm' => 'video/webm',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
        'mkv' => 'video/x-matroska',
        'ogv' => 'video/ogg',
        'mpeg', 'mpg' => 'video/mpeg',
        '3gp' => 'video/3gpp',
        '3g2' => 'video/3gpp2',
        default => 'application/octet-stream',
    };
}

/**
 * @param array{name: string, type: string, tmp_name: string, error: int, size: int} $file
 * @return array{
 *     ok: true,
 *     media_type: string,
 *     content_type: string,
 *     extension: string,
 *     size: int,
 *     tmp_path: string,
 *     original_filename: string
 * }|array{ok: false, error: string}
 */
function validatePostMediaFileEntry(array $file): array
{
    $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'No media uploaded.'];
    }

    if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
        return ['ok' => false, 'error' => 'Media file is too large.'];
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Media upload failed.'];
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return ['ok' => false, 'error' => 'Invalid media upload.'];
    }

    $originalFilename = (string) ($file['name'] ?? 'upload.bin');
    $extension = postMediaExtensionFromFilename($originalFilename);
    if ($extension === '') {
        return ['ok' => false, 'error' => 'Unsupported media type.'];
    }

    $size = filesize($tmpPath);
    if ($size === false || $size < 1) {
        return ['ok' => false, 'error' => 'Invalid media file.'];
    }

    $mime = postMediaDetectMimeType($tmpPath);
    $mediaType = postMediaTypeForMimeAndExtension($mime, $extension);
    if ($mediaType === null) {
        return ['ok' => false, 'error' => 'Unsupported media type.'];
    }

    if (!postMediaMimeAllowedForType($mediaType, $mime, $extension)) {
        return ['ok' => false, 'error' => 'Media type does not match file contents.'];
    }

    $maxBytes = postMediaMaxBytesForType($mediaType);
    if ($size > $maxBytes) {
        $limitLabel = $mediaType === 'video' ? '50 MB' : '15 MB';

        return ['ok' => false, 'error' => 'Media must be ' . $limitLabel . ' or smaller.'];
    }

    return [
        'ok' => true,
        'media_type' => $mediaType,
        'content_type' => postMediaContentTypeForUpload($mediaType, $mime, $extension),
        'extension' => $extension,
        'size' => $size,
        'tmp_path' => $tmpPath,
        'original_filename' => $originalFilename,
    ];
}

/**
 * @param list<array{media_type: string}> $validatedFiles
 */
function validatePostMediaSelection(array $validatedFiles): ?string
{
    $imageCount = 0;
    $videoCount = 0;

    foreach ($validatedFiles as $file) {
        if (($file['media_type'] ?? '') === 'video') {
            $videoCount++;
            continue;
        }

        $imageCount++;
    }

    if ($videoCount > POST_MAX_VIDEOS) {
        return 'Posts can include at most ' . POST_MAX_VIDEOS . ' video.';
    }

    if ($imageCount > POST_MAX_IMAGES) {
        return 'Posts can include at most ' . POST_MAX_IMAGES . ' images.';
    }

    if ($videoCount > 0 && $imageCount > 0) {
        return 'Add either images or a video, not both.';
    }

    return null;
}

/**
 * @return array{ok: true, files: list<array<string, mixed>>}|array{ok: false, error: string}
 */
function validatePostMediaUploads(string $fieldName): array
{
    $entries = postMediaNormalizeUploadField($fieldName);
    $validatedFiles = [];

    foreach ($entries as $file) {
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $result = validatePostMediaFileEntry($file);
        if (!$result['ok']) {
            return $result;
        }

        $validatedFiles[] = $result;
    }

    if ($validatedFiles === []) {
        return ['ok' => false, 'error' => 'No media uploaded.'];
    }

    $selectionError = validatePostMediaSelection($validatedFiles);
    if ($selectionError !== null) {
        return ['ok' => false, 'error' => $selectionError];
    }

    return [
        'ok' => true,
        'files' => $validatedFiles,
    ];
}

/**
 * @return array{
 *     ok: true,
 *     media_type: string,
 *     content_type: string,
 *     extension: string,
 *     size: int,
 *     tmp_path: string,
 *     original_filename: string
 * }|array{ok: false, error: string}
 */
function validatePostMediaUpload(string $fieldName): array
{
    $result = validatePostMediaUploads($fieldName);
    if (!$result['ok']) {
        return $result;
    }

    return $result['files'][0];
}
