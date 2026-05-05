<?php

use App\Models\Doctor;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Complete profile')] class extends Component
{
    public string $name = '';

    public string $name_ar = '';

    public string $about = '';

    public function mount(): void
    {
        $doctor = Auth::guard('doctor')->user();
        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        $this->name = (string) ($doctor->name ?? '');
        $this->name_ar = (string) ($doctor->name_ar ?? '');
        $this->about = (string) ($doctor->about ?? '');
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'about' => ['nullable', 'string', 'max:2000'],
        ]);

        $doctor = Auth::guard('doctor')->user();
        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        $doctor->name = $this->name;
        $doctor->name_ar = $this->name_ar !== '' ? $this->name_ar : null;
        $doctor->about = $this->about !== '' ? $this->about : null;
        $doctor->profile_completed = true;
        $doctor->save();

        $this->redirect(route('doctor.dashboard'), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-xl space-y-8">
    <div class="space-y-2">
        <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.auth.basic_info_title') }}</flux:heading>
        <flux:text class="text-zinc-600">{{ __('doctor.auth.basic_info_subtitle') }}</flux:text>
    </div>

    <form wire:submit="save" class="space-y-4">
        <flux:field>
            <flux:label>{{ __('doctor.auth.name') }}</flux:label>
            <flux:input wire:model="name" />
            <flux:error name="name" />
        </flux:field>
        <flux:field>
            <flux:label>{{ __('doctor.auth.name_ar') }}</flux:label>
            <flux:input wire:model="name_ar" dir="rtl" />
            <flux:error name="name_ar" />
        </flux:field>
        <flux:field>
            <flux:label>{{ __('doctor.auth.about') }}</flux:label>
            <flux:textarea wire:model="about" rows="4" />
            <flux:error name="about" />
        </flux:field>

        <flux:button class="w-full !bg-[#132A6E] !text-white hover:!brightness-95" type="submit" variant="primary">
            {{ __('doctor.auth.save_continue') }}
        </flux:button>
    </form>
</div>
