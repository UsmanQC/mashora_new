<?php

use App\Models\User;
use App\Support\DoctorFavorites;
use App\Support\SpecialistCatalog;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient')] #[Title('Favorites')] class extends Component
{
    /** @var list<array<string, mixed>> */
    public array $favoriteSpecialists = [];

    /** @var array<string, int> */
    public array $likeCounts = [];

    public function mount(): void
    {
        $this->loadFavorites();
    }

    protected function patient(): User
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    protected function loadFavorites(): void
    {
        $this->favoriteSpecialists = SpecialistCatalog::favoritedDoctorCardsForUser($this->patient()->id);
        $this->likeCounts = [];

        foreach ($this->favoriteSpecialists as $specialist) {
            $this->likeCounts[$specialist['id']] = (int) ($specialist['likes'] ?? 0);
        }
    }

    public function toggleLike(string $id): void
    {
        $specialist = collect($this->favoriteSpecialists)->firstWhere('id', $id);
        if ($specialist === null) {
            return;
        }

        $doctorId = $specialist['doctor_database_id'] ?? null;
        if (! filled($doctorId)) {
            return;
        }

        $isLiked = DoctorFavorites::toggle($this->patient()->id, (int) $doctorId);

        if ($isLiked) {
            Flux::toast(
                variant: 'success',
                text: __('specialist_results.like_saved'),
            );

            $this->loadFavorites();

            return;
        }

        Flux::toast(
            variant: 'success',
            text: __('specialist_results.like_removed'),
        );

        $this->favoriteSpecialists = collect($this->favoriteSpecialists)
            ->reject(static fn (array $card): bool => ($card['id'] ?? '') === $id)
            ->values()
            ->all();

        unset($this->likeCounts[$id]);
    }

    public function profilePhotoUrl(): ?string
    {
        $user = Auth::user();

        if ($user === null || ! filled($user->profile_photo_path)) {
            return null;
        }

        return Storage::disk('public')->url((string) $user->profile_photo_path);
    }
};
?>

<div class="patient-luxury-favorites bg-slate-50 pb-[calc(4.75rem+env(safe-area-inset-bottom))] sm:bg-transparent sm:pb-12" data-test="patient-luxury-favorites">
    <div class="sm:hidden">
        @include('partials.patient-luxury-page-header', [
            'title' => __('patient.menu.favorites'),
            'subtitle' => __('patient.menu.favorites_sub'),
            'profilePhotoUrl' => $this->profilePhotoUrl(),
            'userName' => auth()->user()?->name,
            'backUrl' => route('patient.menu'),
            'backLabel' => __('patient.nav.menu'),
            'testId' => 'patient-favorites-header',
        ])
    </div>

    <div class="mx-auto max-w-3xl px-6 pt-5 sm:px-6 sm:py-6 lg:px-8">
        <header class="hidden items-center gap-3 sm:flex">
            <a
                href="{{ route('patient.menu') }}"
                wire:navigate
                class="inline-flex size-10 shrink-0 items-center justify-center rounded-full border border-zinc-200/90 bg-white text-[#10B981] shadow-sm transition hover:bg-zinc-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#10B981]/30"
                aria-label="{{ __('patient.empty_state.back_aria') }}"
            >
                <flux:icon name="chevron-left" variant="outline" class="size-6 rtl:rotate-180" />
            </a>

            <flux:breadcrumbs class="min-w-0 flex-wrap">
                <flux:breadcrumbs.item
                    href="{{ route('patient.menu') }}"
                    separator="slash"
                    class="[&_a]:!text-[#10B981] [&_a]:decoration-[#10B981]/25 [&_a]:hover:!text-[#059669]"
                    wire:navigate
                >
                    {{ __('patient.empty_state.menu_crumb') }}
                </flux:breadcrumbs.item>

                <flux:breadcrumbs.item>
                    {{ __('patient.menu.favorites') }}
                </flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </header>

        @include('partials.patient-favorites-content')
    </div>
</div>
