<?php

namespace App\Filament\Pages;

use App\Models\AiSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageAiSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'AI settings';

    protected static ?string $title = 'AI Manager';

    protected static string|UnitEnum|null $navigationGroup = 'AI Manager';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?string $slug = 'ai-settings';

    protected string $view = 'filament.pages.manage-ai-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $settings = AiSetting::current();

        $this->form->fill([
            'is_active' => $settings->is_active,
            'system_prompt' => $settings->system_prompt,
            'allowed_topics' => $settings->allowed_topics ?? [],
            'blocked_topics' => $settings->blocked_topics ?? [],
            'estimated_cost_cents' => $settings->estimated_cost_cents,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Prompt & restrictions')
                        ->description('Control what the assistant may discuss (Consulta / Awaan only).')
                        ->components([
                            Toggle::make('is_active')
                                ->label('Assistant enabled')
                                ->default(true),
                            Textarea::make('system_prompt')
                                ->label('System prompt')
                                ->rows(8)
                                ->columnSpanFull(),
                            TagsInput::make('allowed_topics')
                                ->label('Allowed topics')
                                ->columnSpanFull(),
                            TagsInput::make('blocked_topics')
                                ->label('Blocked topics')
                                ->columnSpanFull(),
                        ]),
                    Section::make('Cost analytics')
                        ->description('Estimated cumulative cost counter (configure per-request cost in AI_CHATBOT_COST_PER_REQUEST_CENTS).')
                        ->components([
                            TextInput::make('estimated_cost_cents')
                                ->label('Estimated total cost (cents)')
                                ->numeric()
                                ->disabled(),
                        ]),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Save')
                                ->submit('save'),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = AiSetting::current();

        $settings->update([
            'is_active' => (bool) ($data['is_active'] ?? true),
            'system_prompt' => $data['system_prompt'] ?? null,
            'allowed_topics' => $data['allowed_topics'] ?? [],
            'blocked_topics' => $data['blocked_topics'] ?? [],
        ]);

        Notification::make()
            ->success()
            ->title('AI settings saved')
            ->send();
    }
}
