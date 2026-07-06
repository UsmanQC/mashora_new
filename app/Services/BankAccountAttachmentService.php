<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class BankAccountAttachmentService
{
    private const DISK = 'public';

    private const DIRECTORY = 'doctor-bank-attachments';

    /**
     * @return list<string|int>
     */
    public function validationRules(): array
    {
        return ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'];
    }

    public function store(UploadedFile $file, ?string $existingPath = null): string
    {
        $this->delete($existingPath);

        return $file->store(self::DIRECTORY, self::DISK);
    }

    public function delete(?string $path): void
    {
        if (! filled($path) || str_starts_with((string) $path, 'http://') || str_starts_with((string) $path, 'https://')) {
            return;
        }

        if (Storage::disk(self::DISK)->exists((string) $path)) {
            Storage::disk(self::DISK)->delete((string) $path);
        }
    }

    public function url(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        if (str_starts_with((string) $path, 'http://') || str_starts_with((string) $path, 'https://')) {
            return (string) $path;
        }

        return Storage::disk(self::DISK)->url((string) $path);
    }

    public function isImage(?string $path): bool
    {
        if (! filled($path)) {
            return false;
        }

        $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
    }

    public function filename(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        return basename((string) $path);
    }
}
