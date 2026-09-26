<?php

declare(strict_types=1);

namespace App\Security;

use RuntimeException;

/**
 * Upload validation and image processing using GD (bundled with PHP on
 * virtually every shared host — no Imagick/Composer dependency).
 *
 * Mirrors docs/CONTENT_MODEL.md "Media": JPEG/PNG/WebP/AVIF only, 10 MiB
 * max, 40 MP decoded-pixel cap, detected from real image bytes via
 * getimagesize()/finfo — never trusted from the filename extension.
 */
final class MediaValidator
{
    public const MAX_BYTES = 10 * 1024 * 1024;
    public const MAX_PIXELS = 40_000_000;

    private const ALLOWED_MIME_TO_EXT = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @return array{mime:string,ext:string,width:int,height:int}
     * @throws RuntimeException with a user-facing message on rejection
     */
    public static function validate(string $tmpPath, int $size): array
    {
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new RuntimeException('File is too large. Maximum is 10 MiB.');
        }

        // Sniff the real content type — never trust the client-supplied
        // filename extension or Content-Type header.
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($tmpPath);

        if (!array_key_exists($detectedMime, self::ALLOWED_MIME_TO_EXT)) {
            throw new RuntimeException("File type {$detectedMime} is not allowed. Use JPEG, PNG or WebP.");
        }

        $info = @getimagesize($tmpPath);
        if ($info === false) {
            throw new RuntimeException('This file could not be decoded as a supported image.');
        }

        [$width, $height] = $info;
        if ($width <= 0 || $height <= 0) {
            throw new RuntimeException('This file could not be decoded as a supported image.');
        }
        if ($width * $height > self::MAX_PIXELS) {
            throw new RuntimeException(
                "Image is too large ({$width}x{$height} decoded pixels). Maximum is " . number_format(self::MAX_PIXELS) . ' pixels.',
            );
        }

        // Cross-check getimagesize()'s own type detection against finfo —
        // catches polyglot files (e.g. a GIF/HTML polyglot with a spoofed
        // mime) that finfo alone might not flag.
        $expectedGdType = match ($detectedMime) {
            'image/jpeg' => IMAGETYPE_JPEG,
            'image/png' => IMAGETYPE_PNG,
            'image/webp' => IMAGETYPE_WEBP,
            default => null,
        };
        if ($expectedGdType !== null && $info[2] !== $expectedGdType) {
            throw new RuntimeException('This file could not be decoded as a supported image.');
        }

        return [
            'mime' => $detectedMime,
            'ext' => self::ALLOWED_MIME_TO_EXT[$detectedMime],
            'width' => $width,
            'height' => $height,
        ];
    }

    private static function load(string $path, string $mime): \GdImage
    {
        $image = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            default => throw new RuntimeException('Unsupported image type'),
        };
        if ($image === false) {
            throw new RuntimeException('Could not decode image for resizing.');
        }
        return $image;
    }

    /**
     * Creates a resized variant (never upscaling), saved alongside the
     * original. Returns null if the source is already smaller than the
     * target width (docs/CONTENT_MODEL.md "generated dimensions/variants").
     *
     * @return array{filename:string,width:int,height:int,filesize:int}|null
     */
    public static function createVariant(string $sourcePath, string $mime, int $targetWidth, string $outputDir, string $baseFilename, string $sizeName): ?array
    {
        [$sourceWidth, $sourceHeight] = getimagesize($sourcePath);
        if ($sourceWidth <= $targetWidth) {
            return null;
        }

        $image = self::load($sourcePath, $mime);
        $targetHeight = (int) round($sourceHeight * ($targetWidth / $sourceWidth));

        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($mime === 'image/png') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        $ext = self::ALLOWED_MIME_TO_EXT[$mime];
        $filename = "{$baseFilename}-{$sizeName}.{$ext}";
        $path = rtrim($outputDir, '/') . '/' . $filename;

        match ($mime) {
            'image/jpeg' => imagejpeg($resized, $path, 85),
            'image/png' => imagepng($resized, $path, 6),
            'image/webp' => imagewebp($resized, $path, 85),
        };

        imagedestroy($image);
        imagedestroy($resized);

        return [
            'filename' => $filename,
            'width' => $targetWidth,
            'height' => $targetHeight,
            'filesize' => (int) filesize($path),
        ];
    }
}
