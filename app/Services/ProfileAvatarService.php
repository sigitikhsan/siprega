<?php

/**
 * Memvalidasi ulang, memotong, memperkecil, dan meng-encode foto profil menjadi WebP privat.
 * File asli tidak disimpan sehingga metadata dan konten tambahan tidak ikut dipublikasikan.
 */

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfileAvatarService
{
    private const OUTPUT_SIZE = 320;

    public function store(UploadedFile $file, Employee $employee): string
    {
        return $this->storeForOwner($file, 'profile-avatars/'.$employee->id);
    }

    public function storeForAdmin(UploadedFile $file, User $user): string
    {
        return $this->storeForOwner($file, 'admin-profile-avatars/'.$user->id);
    }

    private function storeForOwner(UploadedFile $file, string $directory): string
    {
        $info = @getimagesize($file->getRealPath());
        $mime = $info['mime'] ?? null;
        if (!$info || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw ValidationException::withMessages(['avatar' => 'Foto harus berupa JPG, PNG, atau WebP asli.']);
        }

        $source = $this->createSource($file->getRealPath(), $mime);
        if (!$source) {
            throw ValidationException::withMessages(['avatar' => 'Foto tidak dapat diproses atau rusak.']);
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $cropSize = min($width, $height);
        $sourceX = (int) floor(($width - $cropSize) / 2);
        $sourceY = (int) floor(($height - $cropSize) / 2);
        $output = imagecreatetruecolor(self::OUTPUT_SIZE, self::OUTPUT_SIZE);
        imagealphablending($output, false);
        imagesavealpha($output, true);
        imagefill($output, 0, 0, imagecolorallocatealpha($output, 0, 0, 0, 127));
        imagecopyresampled($output, $source, 0, 0, $sourceX, $sourceY, self::OUTPUT_SIZE, self::OUTPUT_SIZE, $cropSize, $cropSize);

        ob_start();
        $encoded = imagewebp($output, null, 82);
        $contents = ob_get_clean();
        imagedestroy($source);
        imagedestroy($output);

        if (!$encoded || !$contents) {
            throw ValidationException::withMessages(['avatar' => 'Foto gagal dikompresi. Silakan gunakan foto lain.']);
        }

        $path = $directory.'/'.Str::uuid().'.webp';
        Storage::disk('local')->put($path, $contents);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path && (Str::startsWith($path, 'profile-avatars/') || Str::startsWith($path, 'admin-profile-avatars/'))) {
            Storage::disk('local')->delete($path);
        }
    }

    private function createSource(string $path, string $mime)
    {
        if ($mime === 'image/jpeg') return @imagecreatefromjpeg($path);
        if ($mime === 'image/png') return @imagecreatefrompng($path);
        if ($mime === 'image/webp') return @imagecreatefromwebp($path);

        return false;
    }
}
