<?php

use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketService;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient')] #[Title('New support ticket')] class extends Component
{
    public int $categoryId = 0;

    public string $subject = '';

    public string $message = '';

    protected function patient(): User
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    /**
     * @return Collection<int, TicketCategory>
     */
    public function getCategoriesProperty(): Collection
    {
        return TicketCategory::query()
            ->active()
            ->forAudience('patient')
            ->orderBy('sort_order')
            ->get();
    }

    public function save(): void
    {
        $this->validate([
            'categoryId' => ['required', 'integer', 'min:1'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $ticket = app(TicketService::class)->create(
                $this->patient(),
                $this->categoryId,
                $this->subject,
                $this->message,
            );
        } catch (ValidationException $exception) {
            throw $exception;
        }

        Flux::toast(
            variant: 'success',
            text: __('tickets.success', ['number' => $ticket->ticket_number]),
        );

        $this->redirectRoute('patient.support.show', $ticket, navigate: true);
    }

    public function profilePhotoUrl(): ?string
    {
        $user = Auth::user();

        if ($user === null || ! filled($user->profile_photo_path)) {
            return null;
        }

        return Storage::disk('public')->url((string) $user->profile_photo_path);
    }
}; ?>

<div class="patient-luxury-support-create bg-slate-50 pb-[calc(4.75rem+env(safe-area-inset-bottom))] sm:bg-transparent sm:pb-12" data-test="patient-luxury-support-create">
    <div class="sm:hidden">
        @include('partials.patient-luxury-page-header', [
            'title' => __('tickets.new_ticket'),
            'subtitle' => __('tickets.subtitle'),
            'profilePhotoUrl' => $this->profilePhotoUrl(),
            'userName' => auth()->user()?->name,
            'backUrl' => route('patient.support'),
            'backLabel' => __('patient.menu.support'),
            'testId' => 'patient-support-create-header',
        ])
    </div>

    <div class="mx-auto max-w-2xl space-y-5 px-6 pt-5 sm:space-y-6 sm:px-4 sm:py-8">
        <div class="hidden items-start justify-between gap-4 sm:flex">
            <div>
                <flux:heading size="xl" class="font-semibold text-[#10B981]">{{ __('tickets.new_ticket') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-600">{{ __('tickets.subtitle') }}</flux:text>
            </div>
            <flux:button :href="route('patient.support')" wire:navigate variant="ghost" size="sm" icon="arrow-left">
                {{ __('tickets.title') }}
            </flux:button>
        </div>

        <form wire:submit="save" class="space-y-5 rounded-3xl border border-slate-100/80 bg-white p-5 shadow-[0_8px_32px_0_rgba(0,0,0,0.03)] sm:rounded-2xl sm:border-zinc-200/90 sm:shadow-sm">
        <flux:field>
            <flux:label>{{ __('tickets.category_label') }}</flux:label>
            <select
                wire:model="categoryId"
                required
                class="h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm text-zinc-900 focus:border-[#10B981] focus:outline-none focus:ring-2 focus:ring-[#10B981]/20"
            >
                <option value="0">{{ __('tickets.category_label') }}</option>
                @foreach ($this->categories as $category)
                    <option value="{{ $category->id }}">{{ $category->displayName() }}</option>
                @endforeach
            </select>
            <flux:error name="categoryId" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('tickets.subject_label') }}</flux:label>
            <flux:input wire:model="subject" required maxlength="255" />
            <flux:error name="subject" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('tickets.message_label') }}</flux:label>
            <flux:textarea wire:model="message" rows="6" required maxlength="5000" placeholder="{{ __('tickets.message_placeholder') }}" />
            <flux:error name="message" />
        </flux:field>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" class="!bg-[#10B981] !text-white">{{ __('tickets.submit') }}</flux:button>
        </div>
    </form>
    </div>
</div>
