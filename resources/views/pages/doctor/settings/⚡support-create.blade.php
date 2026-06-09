<?php

use App\Models\Doctor;
use App\Models\TicketCategory;
use App\Services\TicketService;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('New support ticket')] class extends Component
{
    public int $categoryId = 0;

    public string $subject = '';

    public string $message = '';

    protected function doctor(): Doctor
    {
        $doctor = Auth::guard('doctor')->user();
        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        return $doctor;
    }

    /**
     * @return Collection<int, TicketCategory>
     */
    public function getCategoriesProperty(): Collection
    {
        return TicketCategory::query()
            ->active()
            ->forAudience('doctor')
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
                $this->doctor(),
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

        $this->redirectRoute('doctor.settings.support.show', $ticket, navigate: true);
    }
}; ?>

<div class="mx-auto max-w-2xl space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('tickets.new_ticket') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-600">{{ __('tickets.subtitle') }}</flux:text>
        </div>
        <flux:button :href="route('doctor.settings.support')" wire:navigate variant="ghost" size="sm" icon="arrow-left">{{ __('tickets.title') }}</flux:button>
    </div>

    <form wire:submit="save" class="space-y-5 rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">
        <flux:field>
            <flux:label>{{ __('tickets.category_label') }}</flux:label>
            <select
                wire:model="categoryId"
                required
                class="h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm text-zinc-900 focus:border-[#132A6E] focus:outline-none focus:ring-2 focus:ring-[#132A6E]/20"
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
            <flux:button type="submit" variant="primary" class="!bg-[#132A6E] !text-white">{{ __('tickets.submit') }}</flux:button>
        </div>
    </form>
</div>
