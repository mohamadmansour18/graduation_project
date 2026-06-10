<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageProcessor
{
    public static function url(?string $path, string $disk = 'public'): ?string
    {
        if (!$path) {
            return null;
        }

        //Disk public it refers to (storage/app/public) , url function return (/storage/$path) , asset convert (/storage/$path) to (http://your-domain.com/storage/$path)
        return asset(Storage::disk($disk)->url($path));
    }

    public static function urlOrDefault(?string $path, string $defaultPath = 'defaults/default-avatar.svg', ?string $disk = 'public'): string {

        if ($path && Storage::disk($disk)->exists($path)) {
            return asset(Storage::disk($disk)->url($path));
        }

        return asset(Storage::disk($disk)->url($defaultPath));
//        return asset(Storage::disk($disk)->url($path ?: $defaultPath));
    }

    public static function uploadImage(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        //storage image in the specified directory and return path of storage not completed url (EX: ImageHelper::uploadImage($request->file('avatar'), 'avatars') return "avatars/abc123.png")
        return $file->store($directory, $disk);
    }

    public static function delete(?string $path, string $disk = 'public'): void
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    public static function replaceImage(?UploadedFile $file, ?string $oldPath, string $directory, string $disk = 'public'): ?string
    {
        if (!$file) {
            return $oldPath;
        }

        self::delete($oldPath, $disk);

        return self::uploadImage($file, $directory, $disk);
    }
}
