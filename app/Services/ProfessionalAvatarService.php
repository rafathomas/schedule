<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfessionalAvatarService
{
    public function replace(?string $currentPath, ?UploadedFile $avatar, bool $remove): ?string
    {
        if ($avatar === null && ! $remove) {
            return $currentPath;
        }

        $this->remove($currentPath);

        if ($avatar === null) {
            return null;
        }

        $storedPath = $avatar->store('professionals', 'public');

        return is_string($storedPath) ? $storedPath : null;
    }

    public function remove(?string $path): void
    {
        if ($path !== null) {
            Storage::disk('public')->delete($path);
        }
    }
}
