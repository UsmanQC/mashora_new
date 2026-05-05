<?php

namespace App\Livewire;

use App\Models\PatientMood;
use App\Support\PatientMoodImage;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class PatientMoodPickerModal extends Component
{
    public bool $showMoodModal = false;

    public ?string $selectedMoodKey = null;

    public string $moodNote = '';

    public bool $shareWithTherapist = false;

    #[On('open-patient-mood-picker')]
    public function listenOpenMoodPicker(): void
    {
        $this->resetMoodForm();
        $this->showMoodModal = true;
    }

    public function updatedShowMoodModal(mixed $value): void
    {
        if (! $value) {
            $this->resetMoodForm();
        }
    }

    public function setMood(string $key): void
    {
        if (! in_array($key, PatientMoodImage::MOOD_KEYS, true)) {
            return;
        }

        $this->selectedMoodKey = $key;
        $this->resetValidation('selectedMoodKey');
    }

    public function saveMood(): void
    {
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        $validated = $this->validate([
            'selectedMoodKey' => ['required', Rule::in(PatientMoodImage::MOOD_KEYS)],
            'moodNote' => ['nullable', 'string', 'max:5000'],
            'shareWithTherapist' => ['boolean'],
        ], [
            'selectedMoodKey.required' => __('patient.mood_tracker_select_mood'),
            'selectedMoodKey.in' => __('patient.mood_tracker_select_mood'),
        ]);

        $loggedOn = now()->timezone(config('app.timezone'))->startOfDay();

        $comments = (($validated['moodNote'] ?? '') !== '')
            ? trim((string) $validated['moodNote'])
            : null;

        PatientMood::query()->create([
            'user_id' => $user->getKey(),
            'mood' => $validated['selectedMoodKey'],
            'comments' => $comments,
            'date' => $loggedOn,
            'is_shared' => (bool) ($validated['shareWithTherapist'] ?? false),
        ]);

        $label = __('patient.mood_selector_options.'.$validated['selectedMoodKey']);

        Flux::toast(variant: 'success', text: __('patient.mood_logged_toast', ['mood' => $label]));

        $this->showMoodModal = false;
    }

    /**
     * Placeholder emoji when no custom mood image exists in `public/images/patient-moods/{key}.*`.
     */
    public function moodEmoji(string $key): string
    {
        return match ($key) {
            'satisfied' => '😌',
            'neutral' => '😐',
            'disappointed' => '😕',
            'anxiety' => '😰',
            'happy' => '😄',
            'sad' => '😔',
            'tired' => '🥱',
            'angry' => '😠',
            default => '•',
        };
    }

    public function moodImageUrl(string $key): ?string
    {
        return PatientMoodImage::url($key);
    }

    protected function resetMoodForm(): void
    {
        $this->selectedMoodKey = null;
        $this->moodNote = '';
        $this->shareWithTherapist = false;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.patient-mood-picker-modal', [
            'moodKeys' => PatientMoodImage::MOOD_KEYS,
        ]);
    }
}
