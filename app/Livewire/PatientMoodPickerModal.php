<?php

namespace App\Livewire;

use App\Services\PatientMoodLogService;
use App\Support\PatientMoodImage;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class PatientMoodPickerModal extends Component
{
    public bool $showMoodModal = false;

    public bool $showFutureMoodDialog = false;

    public bool $showAlreadyLoggedMoodDialog = false;

    public bool $showOnlyTodayMoodDialog = false;

    public ?string $selectedMoodKey = null;

    public string $moodNote = '';

    public bool $shareWithTherapist = false;

    #[On('open-patient-mood-picker')]
    public function listenOpenMoodPicker(?string $dateIso = null, ?string $moodKey = null): void
    {
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        /** @var PatientMoodLogService $moodLog */
        $moodLog = app(PatientMoodLogService::class);
        $today = $moodLog->todayDate();

        $selected = filled($dateIso)
            ? Carbon::parse($dateIso)->timezone(config('app.timezone'))->startOfDay()
            : $today;

        if ($selected->isAfter($today)) {
            $this->showFutureMoodDialog = true;

            return;
        }

        if ($selected->isBefore($today)) {
            $this->showOnlyTodayMoodDialog = true;

            return;
        }

        if ($moodLog->hasMoodForToday($user)) {
            $this->showAlreadyLoggedMoodDialog = true;

            return;
        }

        $this->resetMoodForm();

        if (filled($moodKey) && in_array($moodKey, PatientMoodImage::MOOD_KEYS, true)) {
            $this->selectedMoodKey = $moodKey;
        }

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

        /** @var PatientMoodLogService $moodLog */
        $moodLog = app(PatientMoodLogService::class);

        if ($moodLog->hasMoodForToday($user)) {
            $this->showMoodModal = false;
            $this->showAlreadyLoggedMoodDialog = true;

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

        $comments = (($validated['moodNote'] ?? '') !== '')
            ? trim((string) $validated['moodNote'])
            : null;

        $moodLog->logMoodForToday(
            $user,
            $validated['selectedMoodKey'],
            $comments,
            (bool) ($validated['shareWithTherapist'] ?? false),
        );

        $label = __('patient.mood_selector_options.'.$validated['selectedMoodKey']);

        Flux::toast(variant: 'success', text: __('patient.mood_logged_toast', ['mood' => $label]));

        $this->showMoodModal = false;
        $this->dispatch('patient-mood-saved');
    }

    /**
     * Placeholder emoji when no custom mood image exists in `public/images/patient-moods/{key}.*`.
     */
    public function moodEmoji(string $key): string
    {
        return PatientMoodImage::emoji($key);
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
