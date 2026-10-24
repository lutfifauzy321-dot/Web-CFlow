<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class UploadService
{
    public static function storeImage(UploadedFile $file, string $directory): string
    {
        if (!$file->isValid()) {
            throw new RuntimeException('Upload gagal, coba lagi.');
        }

        $extension = match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new RuntimeException('Format harus JPG, PNG, atau WEBP.'),
        };

        $name = Str::random(24) . '.' . $extension;
        $destination = public_path('uploads/' . $directory);
        File::ensureDirectoryExists($destination);
        $file->move($destination, $name);

        return $name;
    }

    public static function removeReceipt(?string $name): void
    {
        self::remove('receipts', $name);
    }

    public static function removeAvatar(?string $avatar): void
    {
        if (!str_starts_with((string) $avatar, 'local:')) {
            return;
        }

        self::remove('avatars', basename(substr($avatar, 6)));
    }

    private static function remove(string $directory, ?string $name): void
    {
        if (!$name) {
            return;
        }

        $path = public_path('uploads/' . $directory . '/' . basename($name));
        if (File::exists($path) && !File::delete($path)) {
            throw new RuntimeException('File unggahan tidak dapat dihapus.');
        }
    }
}
