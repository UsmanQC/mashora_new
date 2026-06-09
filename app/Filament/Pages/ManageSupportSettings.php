<?php

namespace App\Filament\Pages;

use App\Services\TicketService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as ActionsSchema;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageSupportSettings extends Page
{
    use CanUseDatabaseTransactions;

    protected static ?string $title = 'Support settings';

    protected static ?string $navigationLabel = 'Support settings';

    protected static string|UnitEnum|null $navigationGroup = 'Clinical & billing';

    protected static ?int $navigationSort = 66;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $slug = 'support-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->data = [
            'notification_email' => app(TicketService::class)->notificationEmail(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('support-settings-form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        ActionsSchema::make([
                            Action::make('save')
                                ->label(__('Save'))
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ])
                            ->alignment(Alignment::Start)
                            ->fullWidth(false),
                    ]),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Ticket notifications'))
                    ->description(__('New tickets from patients and doctors are emailed to this address.'))
                    ->components([
                        TextInput::make('notification_email')
                            ->label(__('Notification email'))
                            ->email()
                            ->maxLength(255)
                            ->placeholder('support@example.com'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        app(TicketService::class)->setNotificationEmail($data['notification_email'] ?? null);

        Notification::make()
            ->title(__('Saved'))
            ->success()
            ->send();
    }
}
