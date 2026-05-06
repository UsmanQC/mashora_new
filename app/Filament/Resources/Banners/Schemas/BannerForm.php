<?php

namespace App\Filament\Resources\Banners\Schemas;

use App\Http\Controllers\Admin\BannersController;
use App\Traits\UploadAble;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Aligned with Mashorapwa-prod {@see BannersController}:
 * title (required), image (required on create), status.
 *
 * Prod stored uploads under {@code public/banners/} via {@see UploadAble::uploadOne()}
 * with folder name {@code banners}. Here Filament uses disk {@code public} directory {@code banners}
 * ({@code storage/app/public/banners} → served as {@code /storage/banners/...}).
 */
class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Banner'))
                    ->columns(2)
                    ->components([
                        TextInput::make('title')
                            ->label(__('Title'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        FileUpload::make('image_path')
                            ->label(__('Image'))
                            ->image()
                            ->disk('public')
                            ->directory('banners')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->required(fn ($livewire): bool => $livewire instanceof CreateRecord)
                            ->columnSpanFull(),
                        TextInput::make('order')
                            ->label(__('Sort order'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Toggle::make('status')
                            ->label(__('Active'))
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }
}
