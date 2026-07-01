<?php

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('FAQ')
                    ->columns(2)
                    ->components([
                        TextInput::make('question')
                            ->label('Question (English)')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('question_ar')
                            ->label('Question (Arabic)')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('answer')
                            ->label('Answer (English)')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('answer_ar')
                            ->label('Answer (Arabic)')
                            ->rows(4)
                            ->columnSpanFull(),
                        TextInput::make('category')
                            ->maxLength(100),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }
}
