<?php

declare(strict_types=1);

const IMAGE_COMPRESS_PROFILE_MAX_DIMENSION = 1024;
const IMAGE_COMPRESS_POST_MAX_DIMENSION = 1920;
const IMAGE_COMPRESS_JPEG_QUALITY = 82;
const IMAGE_COMPRESS_WEBP_QUALITY = 82;
const IMAGE_COMPRESS_PNG_LEVEL = 6;

function imageCompressGdAvailable(): bool
{
    return extension_loaded('gd') && function_exists('imagecreatetruecolor');
}

function imageCompressShouldProcess(string $extension, ?string $mediaType = null): bool
{
    if (!imageCompressGdAvailable()) {
        return false;
    }

    if ($mediaType === 'video') {
        return false;
    }

    if ($mediaType === 'gif' || strtolower($extension) === 'gif') {
        return false;
    }

    return in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'avif'], true);
}

function imageCompressReplaceFilenameExtension(string $filename, string $newExtension): string
{
    $basename = basename(str_replace('\\', '/', $filename));
    $stem = pathinfo($basename, PATHINFO_FILENAME);
    $stem = is_string($stem) && $stem !== '' ? $stem : 'upload';

    return $stem . '.' . strtolower($newExtension);
}

/**
 * @return \GdImage|null
 */
function imageCompressCreateFromPath(string $tmpPath, string $extension)
{
    return match (strtolower($extension)) {
        'jpg', 'jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($tmpPath) : null,
        'png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($tmpPath) : null,
        'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmpPath) : null,
        'bmp' => function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($tmpPath) : null,
        'avif' => function_exists('imagecreatefromavif') ? @imagecreatefromavif($tmpPath) : null,
        default => null,
    };
}

function imageCompressPngHasTransparency(\GdImage $image): bool
{
    if (!function_exists('imageistruecolor') || !imageistruecolor($image)) {
        return true;
    }

    $width = imagesx($image);
    $height = imagesy($image);

    if ($width < 1 || $height < 1) {
        return false;
    }

    $samplePoints = [
        [0, 0],
        [$width - 1, 0],
        [0, $height - 1],
        [(int) floor($width / 2), (int) floor($height / 2)],
    ];

    foreach ($samplePoints as [$x, $y]) {
        $rgba = imagecolorat($image, $x, $y);
        $alpha = ($rgba >> 24) & 0x7F;

        if ($alpha > 0) {
            return true;
        }
    }

    return false;
}

/**
 * @return array{0: \GdImage, 1: int, 2: int}
 */
function imageCompressResizeToMaxDimension(\GdImage $source, int $maxDimension): array
{
    $width = imagesx($source);
    $height = imagesy($source);

    if ($width < 1 || $height < 1) {
        return [$source, $width, $height];
    }

    $longest = max($width, $height);
    if ($longest <= $maxDimension) {
        return [$source, $width, $height];
    }

    $scale = $maxDimension / $longest;
    $targetWidth = max(1, (int) round($width * $scale));
    $targetHeight = max(1, (int) round($height * $scale));

    $resized = imagecreatetruecolor($targetWidth, $targetHeight);
    if ($resized === false) {
        return [$source, $width, $height];
    }

    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
    if ($transparent !== false) {
        imagefilledrectangle($resized, 0, 0, $targetWidth, $targetHeight, $transparent);
    }
    imagealphablending($resized, true);

    imagecopyresampled(
        $resized,
        $source,
        0,
        0,
        0,
        0,
        $targetWidth,
        $targetHeight,
        $width,
        $height
    );

    imagedestroy($source);

    return [$resized, $targetWidth, $targetHeight];
}

/**
 * @return array{extension: string, content_type: string}
 */
function imageCompressOutputFormat(string $sourceExtension, \GdImage $image): array
{
    if (strtolower($sourceExtension) === 'bmp') {
        return ['extension' => 'jpg', 'content_type' => 'image/jpeg'];
    }

    if (strtolower($sourceExtension) === 'png' && imageCompressPngHasTransparency($image)) {
        return ['extension' => 'png', 'content_type' => 'image/png'];
    }

    if (strtolower($sourceExtension) === 'png') {
        return ['extension' => 'jpg', 'content_type' => 'image/jpeg'];
    }

    return match (strtolower($sourceExtension)) {
        'webp' => ['extension' => 'webp', 'content_type' => 'image/webp'],
        'avif' => function_exists('imageavif')
            ? ['extension' => 'avif', 'content_type' => 'image/avif']
            : ['extension' => 'jpg', 'content_type' => 'image/jpeg'],
        'jpg', 'jpeg' => ['extension' => 'jpg', 'content_type' => 'image/jpeg'],
        default => ['extension' => 'jpg', 'content_type' => 'image/jpeg'],
    };
}

function imageCompressWriteImage(\GdImage $image, string $outputPath, string $outputExtension): bool
{
    return match (strtolower($outputExtension)) {
        'png' => imagepng($image, $outputPath, IMAGE_COMPRESS_PNG_LEVEL),
        'webp' => imagewebp($image, $outputPath, IMAGE_COMPRESS_WEBP_QUALITY),
        'avif' => function_exists('imageavif')
            ? imageavif($image, $outputPath, IMAGE_COMPRESS_WEBP_QUALITY)
            : imagejpeg($image, $outputPath, IMAGE_COMPRESS_JPEG_QUALITY),
        default => imagejpeg($image, $outputPath, IMAGE_COMPRESS_JPEG_QUALITY),
    };
}

/**
 * @return array{
 *     ok: true,
 *     tmp_path: string,
 *     extension: string,
 *     content_type: string,
 *     original_filename: string
 * }|array{ok: false}
 */
function imageCompressFile(
    string $tmpPath,
    string $extension,
    string $originalFilename,
    string $contentType,
    int $maxDimension
): array {
    if (!imageCompressShouldProcess($extension)) {
        return ['ok' => false];
    }

    $source = imageCompressCreateFromPath($tmpPath, $extension);
    if (!$source instanceof \GdImage) {
        return ['ok' => false];
    }

    [$image, , ] = imageCompressResizeToMaxDimension($source, $maxDimension);
    $output = imageCompressOutputFormat($extension, $image);
    $outputExtension = $output['extension'];

    if ($outputExtension === 'jpg' && $output['content_type'] === 'image/jpeg') {
        imagealphablending($image, true);
        imagesavealpha($image, false);
        $flattened = imagecreatetruecolor(imagesx($image), imagesy($image));
        if ($flattened instanceof \GdImage) {
            $background = imagecolorallocate($flattened, 255, 255, 255);
            if ($background !== false) {
                imagefilledrectangle($flattened, 0, 0, imagesx($image), imagesy($image), $background);
                imagecopy($flattened, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
                imagedestroy($image);
                $image = $flattened;
            }
        }
    }

    $outputPath = tempnam(sys_get_temp_dir(), 'imgcmp_');
    if ($outputPath === false) {
        imagedestroy($image);

        return ['ok' => false];
    }

    $targetPath = $outputPath . '.' . $outputExtension;
    if (!@rename($outputPath, $targetPath)) {
        @unlink($outputPath);
        $targetPath = $outputPath;
    }

    $written = imageCompressWriteImage($image, $targetPath, $outputExtension);
    imagedestroy($image);

    if (!$written || !is_file($targetPath) || filesize($targetPath) < 1) {
        @unlink($targetPath);

        return ['ok' => false];
    }

    $originalSize = filesize($tmpPath);
    $compressedSize = filesize($targetPath);
    if (
        $originalSize !== false
        && $compressedSize !== false
        && $compressedSize >= $originalSize
        && strtolower($extension) === strtolower($outputExtension)
    ) {
        @unlink($targetPath);

        return ['ok' => false];
    }

    return [
        'ok' => true,
        'tmp_path' => $targetPath,
        'extension' => $outputExtension,
        'content_type' => $output['content_type'],
        'original_filename' => imageCompressReplaceFilenameExtension($originalFilename, $outputExtension),
    ];
}

/**
 * @param array{
 *     tmp_path: string,
 *     extension?: string,
 *     content_type: string,
 *     original_filename: string,
 *     media_type?: string
 * } $validated
 * @return array<string, mixed>
 */
function compressValidatedImageForUpload(array $validated, int $maxDimension): array
{
    $extension = strtolower((string) ($validated['extension'] ?? postMediaExtensionFromFilename($validated['original_filename'])));
    $mediaType = isset($validated['media_type']) ? (string) $validated['media_type'] : null;

    if (!imageCompressShouldProcess($extension, $mediaType)) {
        return $validated;
    }

    $result = imageCompressFile(
        $validated['tmp_path'],
        $extension,
        $validated['original_filename'],
        $validated['content_type'],
        $maxDimension
    );

    if (!$result['ok']) {
        return $validated;
    }

    $validated['tmp_path'] = $result['tmp_path'];
    $validated['extension'] = $result['extension'];
    $validated['content_type'] = $result['content_type'];
    $validated['original_filename'] = $result['original_filename'];

    if (isset($validated['size'])) {
        $size = filesize($result['tmp_path']);
        if ($size !== false) {
            $validated['size'] = $size;
        }
    }

    return $validated;
}
