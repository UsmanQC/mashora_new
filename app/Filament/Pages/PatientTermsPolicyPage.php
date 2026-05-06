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
 * Mashorapwa-prod sidebar linked {@code admin/pages/4/edit} — terms for the patient app.
 */
final class PatientTermsPolicyPage extends ManageCmsPage
{
    protected static ?string $title = 'Terms — Patient app';

    protected static ?string $navigationLabel = 'Patient App';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Terms';

    protected static ?int $navigationSort = 64;

    protected static ?string $slug = 'patient-terms-policy';

    protected static function cmsUseFor(): string
    {
        return 'patient';
    }

    protected static function defaultSlug(): string
    {
        return 'terms-patient';
    }

    protected static function legacyPageId(): ?int
    {
        return 4;
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
