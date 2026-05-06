<?php

namespace App\Filament\Pages;

use App\Http\Controllers\Admin\PagesController;
use App\Models\Page as CmsPage;
use Database\Seeders\PageSeeder;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as ActionsSchema;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Throwable;

/**
 * Edit one {@see CmsPage} row (privacy or terms) using the same fields as Mashorapwa-prod
 * {@see PagesController}: title, title_ar, content, content_ar.
 *
 * @property-read Schema $form
 */
abstract class ManageCmsPage extends Page
{
    use CanUseDatabaseTransactions;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    #[Locked]
    public ?CmsPage $cmsPage = null;

    public function mount(): void
    {
        $this->cmsPage = $this->resolveCmsPage();

        $this->fillForm();
    }

    protected function resolveCmsPage(): CmsPage
    {
        $slug = static::defaultSlug();

        $page = CmsPage::query()->where('slug', $slug)->first();

        if (! $page && ($legacyId = static::legacyPageId())) {
            $page = CmsPage::query()->find($legacyId);
            if ($page && blank($page->slug)) {
                $page->forceFill(['slug' => $slug])->save();
                $page = $page->fresh();
            }
        }

        if (! $page) {
            $defaults = static::bootstrapDefaults();
            $page = CmsPage::create([
                'slug' => $slug,
                'use_for' => static::cmsUseFor(),
                'title' => $defaults['title'],
                'title_ar' => $defaults['title_ar'],
                'content' => '<p></p>',
                'content_ar' => '<p></p>',
            ]);
        }

        return $page->fresh();
    }

    /**
     * Prod {@see PageSeeder} IDs: privacy doctor 1 / terms doctor 2 / privacy patient 3 / terms patient 4.
     */
    protected static function legacyPageId(): ?int
    {
        return null;
    }

    /**
     * @return array{title: string, title_ar: string}
     */
    abstract protected static function bootstrapDefaults(): array;

    abstract protected static function formSectionHeading(): string;

    protected static function formSectionDescription(): string
    {
        return __('Same fields as prod admin Pages: title, title_ar, content, content_ar.');
    }

    /**
     * {@see CmsPage::$use_for} marker from prod PageSeeder (`doctor` / `patient`).
     */
    abstract protected static function cmsUseFor(): string;

    /**
     * Stable {@see CmsPage::$slug}; used for lookup and when assigning slug to legacy rows.
     */
    abstract protected static function defaultSlug(): string;

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $data = $this->cmsPage->only([
            'title',
            'title_ar',
            'content',
            'content_ar',
        ]);

        $data = $this->mutateFormDataBeforeFill($data);

        $this->form->fill($data);

        $this->callHook('afterFill');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeSave($data);

            $this->callHook('beforeSave');

            $this->handleRecordUpdate($this->cmsPage, $data);

            $this->callHook('afterSave');
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->commitDatabaseTransaction();

        Notification::make()
            ->success()
            ->title(__('Saved'))
            ->send();

        $this->cmsPage->refresh();
        $this->fillForm();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        return $record;
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->operation('edit')
            ->model($this->cmsPage)
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(static::formSectionHeading())
                    ->description(static::formSectionDescription())
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
                        Textarea::make('content')
                            ->label(__('Content (English)'))
                            ->required()
                            ->rows(18)
                            ->columnSpanFull(),
                        Textarea::make('content_ar')
                            ->label(__('Content (Arabic)'))
                            ->required()
                            ->rows(18)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label(__('Save'))
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? parent::getTitle();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                ActionsSchema::make($this->getFormActions())
                    ->alignment(Alignment::Start)
                    ->fullWidth(false)
                    ->sticky(static::$formActionsAreSticky)
                    ->key('form-actions'),
            ]);
    }
}
