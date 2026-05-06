<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Mashorapwa-prod sidebar linked {@code admin/pages/2/edit} — terms for the doctor app.
 */
final class DoctorTermsPolicyPage extends ManageCmsPage
{
    protected static ?string $title = 'Terms — Doctor app';

    protected static ?string $navigationLabel = 'Doctor App';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Terms';

    protected static ?int $navigationSort = 63;

    protected static ?string $slug = 'doctor-terms-policy';

    protected static function cmsUseFor(): string
    {
        return 'doctor';
    }

    protected static function defaultSlug(): string
    {
        return 'terms-doctor';
    }

    protected static function legacyPageId(): ?int
    {
        return 2;
    }

    /**
     * @return array{title: string, title_ar: string}
     */
    protected static function bootstrapDefaults(): array
    {
        return [
            'title' => 'Terms & Conditions',
            'title_ar' => 'البنود و الظروف',
        ];
    }

    protected static function formSectionHeading(): string
    {
        return __('Terms & conditions content');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(self::formSectionHeading())
                    ->description(self::formSectionDescription())
                    ->columns(2)
                    ->components([
                        TextInput::make('title')
                            ->label(__('Title (English)'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('title_ar')
                            ->label(__('Title (Arabic)'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        RichEditor::make('content')
                            ->label(__('Content (English)'))
                            ->required()
                            ->columnSpanFull()
                            ->extraInputAttributes([
                                'dir' => 'ltr',
                                'lang' => 'en',
                            ]),
                        RichEditor::make('content_ar')
                            ->label(__('Content (Arabic)'))
                            ->required()
                            ->columnSpanFull()
                            ->extraInputAttributes([
                                'dir' => 'rtl',
                                'lang' => 'ar',
                            ]),
                    ]),
            ]);
    }
}
