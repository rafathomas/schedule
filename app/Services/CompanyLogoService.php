<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CompanyLogoService
{
    public function replace(
        string $companyUuid,
        ?string $currentPath,
        ?UploadedFile $logo,
        bool $remove,
    ): ?string {
        if ($logo === null && ! $remove) {
            return $currentPath;
        }

        if ($logo === null) {
            $this->remove($currentPath);

            return null;
        }

        $storedPath = $logo->store("companies/{$companyUuid}/branding", 'public');

        if (! is_string($storedPath)) {
            return $currentPath;
        }

        $this->remove($currentPath);

        return $storedPath;
    }

    public function remove(?string $path): void
    {
        if ($path !== null) {
            Storage::disk('public')->delete($path);
        }
    }
}
