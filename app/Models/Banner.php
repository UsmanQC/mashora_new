<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    protected $table = 'banners';

    protected $primaryKey = 'id';

    /**
     * @var list<string>
     */
    protected $fillable = ['title', 'image_path', 'order', 'status'];

    /**
     * @var list<string>
     */
    protected $hidden = ['created_at', 'updated_at'];

    /**
     * @var list<string>
     */
    protected $appends = [
        'image',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Banner $banner): void {
            static::removeStoredImage($banner->image_path);
        });
    }

    /**
     * Remove a file from disk (storage {@code public} disk or legacy {@see public_path()}).
     */
    public static function removeStoredImage(?string $path): void
    {
        if (! $path) {
            return;
        }
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);

            return;
        }
        $legacy = public_path($path);
        if (is_file($legacy)) {
            @unlink($legacy);
        }
    }

    /**
     * Full URL for API / previews (legacy relative paths + storage disk paths).
     */
    public function getImageAttribute(): string
    {
        if (! $this->image_path) {
            return '';
        }

        if (preg_match('#^https?://#i', $this->image_path)) {
            return $this->image_path;
        }

        if (Storage::disk('public')->exists($this->image_path)) {
            return Storage::disk('public')->url($this->image_path);
        }

        return asset($this->image_path);
    }
}
