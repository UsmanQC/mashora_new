<?php

namespace App\Filament\Resources\Banners\Pages;

use App\Filament\Resources\Banners\BannerResource;
use App\Models\Banner;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBanner extends EditRecord
{
    protected static string $resource = BannerResource::class;

    /** @var string|null Previous relative path before save (replace removes old file). */
    protected ?string $previousImagePath = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->previousImagePath = $this->record->getOriginal('image_path');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $newPath = $this->record->fresh()?->image_path;
        if (
            $this->previousImagePath
            && $this->previousImagePath !== $newPath
        ) {
            Banner::removeStoredImage($this->previousImagePath);
        }
    }
}
