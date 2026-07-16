<?php

use App\Models\Degree;
use App\Models\Doctor;
use App\Models\Speciality;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::doctor')] #[Title('Complete profile')] class extends Component
{
    use WithFileUploads;

    public int $step = 1;

    public string $name = '';

    public string $name_ar = '';

    public string $about = '';

    public string $about_ar = '';

    public mixed $profile_photo = null;

    public ?string $gender = null;

    /** @var int|string|null Degree id from the dropdown (Livewire often receives a string). */
    public $degree_id = null;

    /** @var list<int> */
    public array $speciality_ids = [];

    public string $registration_number = '';

    public ?int $experience = null;

    public string $medical_career_level = '';

    public ?string $spoken_languages = null;

    public mixed $certificate = null;

    /** PNG data URL from signature pad; persisted to {@see Doctor::$signature}. */
    public ?string $signatureDataUrl = null;

    protected function doctor(): Doctor
    {
        $doctor = Auth::guard('doctor')->user();

        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        return $doctor;
    }

    public function mount(): void
    {
        $doctor = $this->doctor();

        $requestedStep = (int) request()->query('step', 1);
        if ($requestedStep >= 1 && $requestedStep <= 3) {
            $this->step = $requestedStep;
        }

        $this->name = (string) ($doctor->name ?? '');
        $this->name_ar = (string) ($doctor->name_ar ?? '');
        $this->about = (string) ($doctor->about ?? '');
        $this->about_ar = (string) ($doctor->about_ar ?? '');
        $this->gender = $doctor->gender;
        $this->degree_id = $doctor->degree_id !== null ? (int) $doctor->degree_id : null;
        $doctor->loadMissing('specialities');

        if ($doctor->profile_completed || filled($doctor->registration_number)) {
            $this->speciality_ids = $doctor->specialities
                ->pluck('id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->values()
                ->all();
        } else {
            $this->speciality_ids = [];

            if ($doctor->specialities()->exists()) {
                $doctor->specialities()->detach();
                $doctor->updateQuietly(['speciality_id' => null]);
            }
        }

        $this->registration_number = (string) ($doctor->registration_number ?? '');
        $this->experience = $doctor->experience !== null ? (int) $doctor->experience : null;
        $this->medical_career_level = (string) ($doctor->medical_career_level ?? '');
        $this->spoken_languages = $doctor->spoken_languages;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Degree>
     */
    #[Computed]
    public function degrees()
    {
        return Degree::query()->where('status', true)->orderBy('id')->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Speciality>
     */
    #[Computed]
    public function specialities()
    {
        return Speciality::query()->where('status', true)->orderBy('title')->get();
    }

    public function goBack(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function updatedProfilePhoto(): void
    {
        $this->resetValidation('profile_photo');
    }

    public function updatedSpecialityIds(): void
    {
        $this->resetValidation('speciality_ids');
    }

    public function updatedDegreeId(): void
    {
        $this->resetValidation('degree_id');
    }

    public function nextFromBasic(): void
    {
        $doctor = $this->doctor();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'about' => ['required', 'string', 'max:2000'],
            'about_ar' => ['required', 'string', 'max:2000'],
            'profile_photo' => [
                Rule::requiredIf(fn (): bool => ! filled($doctor->profile_photo_path)),
                'nullable',
                'image',
                'max:2048',
            ],
        ]);

        $doctor->name = $this->name;
        $doctor->name_ar = $this->name_ar;
        $doctor->about = $this->about;
        $doctor->about_ar = $this->about_ar;

        if ($this->profile_photo) {
            $newPhotoPath = $this->storeProfilePhotoAsWebp();
            $oldPhotoPath = $doctor->profile_photo_path;
            $doctor->profile_photo_path = $newPhotoPath;
            if (filled($oldPhotoPath) && Storage::disk('public')->exists((string) $oldPhotoPath)) {
                Storage::disk('public')->delete((string) $oldPhotoPath);
            }
        }

        $doctor->save();

        $this->profile_photo = null;

        $this->step = 2;
    }

    /**
     * Convert the uploaded avatar to an optimized WebP (max 512px, quality 80)
     * for fast loading. Falls back to storing the original when GD/WebP is
     * unavailable on the host.
     */
    private function storeProfilePhotoAsWebp(): string
    {
        $file = $this->profile_photo;
        $contents = @file_get_contents($file->getRealPath());

        if ($contents === false || ! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            return $file->store('doctors', 'public');
        }

        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            return $file->store('doctors', 'public');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $maxDimension = 512;
        $scale = min(1, $maxDimension / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        $encoded = imagewebp($canvas, null, 80);
        $webp = ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        if (! $encoded || ! is_string($webp) || $webp === '') {
            return $file->store('doctors', 'public');
        }

        $path = 'doctors/'.Str::uuid()->toString().'.webp';
        Storage::disk('public')->put($path, $webp);

        return $path;
    }

    public function removeCertificate(): void
    {
        $this->certificate = null;
        $this->resetErrorBag('certificate');
    }

    public function nextFromProfessional(): void
    {
        $this->speciality_ids = array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            $this->speciality_ids,
        )));

        $this->validate([
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'degree_id' => ['required', 'integer', Rule::exists('degrees', 'id')->where('status', 1)],
            'speciality_ids' => ['required', 'array', 'min:1'],
            'speciality_ids.*' => ['integer', Rule::exists(Speciality::class, 'id')],
            'registration_number' => ['required', 'string', 'max:120'],
            'experience' => ['required', 'integer', 'min:0', 'max:80'],
            'medical_career_level' => ['nullable', 'string', 'max:120'],
            'spoken_languages' => ['required', Rule::in(['ar', 'en', 'ar_en'])],
        ]);

        $doctor = $this->doctor();
        $doctor->gender = $this->gender;
        $doctor->degree_id = (int) $this->degree_id;
        $doctor->registration_number = $this->registration_number;
        $doctor->experience = $this->experience;
        $doctor->medical_career_level = $this->medical_career_level !== '' ? $this->medical_career_level : null;
        $doctor->spoken_languages = $this->spoken_languages;
        $doctor->speciality_id = $this->speciality_ids[0] ?? null;
        $doctor->save();

        $doctor->specialities()->sync($this->speciality_ids);

        $this->step = 3;
    }

    public function finishOnboarding(): void
    {
        $this->validate([
            'certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'signatureDataUrl' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || ! str_starts_with($value, 'data:image/png;base64,')) {
                    $fail(__('doctor.auth.signature_invalid'));

                    return;
                }
                $comma = strpos($value, ',');
                if ($comma === false) {
                    $fail(__('doctor.auth.signature_invalid'));

                    return;
                }
                $raw = base64_decode(substr($value, $comma + 1), true);
                if ($raw === false) {
                    $fail(__('doctor.auth.signature_invalid'));

                    return;
                }
                $len = strlen($raw);
                if ($len < 64) {
                    $fail(__('doctor.auth.signature_too_small'));

                    return;
                }
                if ($len > 2_097_152) {
                    $fail(__('doctor.auth.signature_too_large'));

                    return;
                }
            }],
        ]);

        $doctor = $this->doctor();

        $path = $this->certificate->store('doctor-documents', 'public');

        $oldPath = $doctor->profile_detail_path;
        if (filled($oldPath) && ! str_starts_with((string) $oldPath, 'http://') && ! str_starts_with((string) $oldPath, 'https://')) {
            if (Storage::disk('public')->exists((string) $oldPath)) {
                Storage::disk('public')->delete((string) $oldPath);
            }
        }

        $doctor->profile_detail_path = $path;

        $comma = strpos((string) $this->signatureDataUrl, ',');
        $pngBinary = base64_decode(substr((string) $this->signatureDataUrl, $comma !== false ? $comma + 1 : 0), true);
        $sigFilename = 'doctor-signatures/'.Str::uuid()->toString().'.png';
        Storage::disk('public')->put($sigFilename, $pngBinary);

        $oldSig = $doctor->signature;
        if (filled($oldSig) && ! str_starts_with((string) $oldSig, 'http://') && ! str_starts_with((string) $oldSig, 'https://')) {
            if (Storage::disk('public')->exists((string) $oldSig)) {
                Storage::disk('public')->delete((string) $oldSig);
            }
        }
        $doctor->signature = $sigFilename;

        $doctor->save();

        $this->redirect(route('doctor.register.bank-account'), navigate: true);
    }

    /**
     * Single request: avoids a race between $wire.set('signatureDataUrl') and finishOnboarding().
     */
    public function finishDocuments(string $signaturePngDataUrl): void
    {
        $this->signatureDataUrl = $signaturePngDataUrl;
        $this->finishOnboarding();
    }
}; ?>

@php
    $localeIsAr = app()->getLocale() === 'ar';
@endphp

<div class="doctor-onboarding-basic mx-auto max-w-lg space-y-5 pb-10 sm:max-w-2xl sm:space-y-8 sm:pb-8">
    @once
        @push('scripts')
            <script>
                (function () {
                    const factory = () => ({
                        drawing: false,
                        hasInk: false,
                        lastX: 0,
                        lastY: 0,
                        ctx: null,
                        dpr: 1,
                        padW: 0,
                        padH: 0,
                        initPad() {
                            this.$nextTick(() => {
                                const run = () => {
                                    const c = this.$refs.sigCanvas;
                                    if (!c) {
                                        return;
                                    }
                                    const rect = c.getBoundingClientRect();
                                    this.dpr = window.devicePixelRatio || 1;
                                    let w = Math.max(1, Math.floor(rect.width));
                                    let h = Math.max(1, Math.floor(rect.height));
                                    if (w < 2 || h < 2) {
                                        w = Math.max(w, 320);
                                        h = Math.max(h, 176);
                                        c.style.minHeight = h + 'px';
                                    }
                                    this.padW = w;
                                    this.padH = h;
                                    c.width = Math.floor(w * this.dpr);
                                    c.height = Math.floor(h * this.dpr);
                                    this.ctx = c.getContext('2d');
                                    this.ctx.setTransform(1, 0, 0, 1, 0, 0);
                                    this.ctx.scale(this.dpr, this.dpr);
                                    this.ctx.fillStyle = '#ffffff';
                                    this.ctx.fillRect(0, 0, w, h);
                                    this.ctx.strokeStyle = '#0f172a';
                                    this.ctx.lineWidth = 2;
                                    this.ctx.lineCap = 'round';
                                    this.ctx.lineJoin = 'round';
                                    this.hasInk = false;
                                    this.drawing = false;
                                };
                                requestAnimationFrame(() => requestAnimationFrame(run));
                            });
                        },
                        pos(e) {
                            const c = this.$refs.sigCanvas;
                            const r = c.getBoundingClientRect();
                            const t = e.touches && e.touches[0];
                            const clientX = t ? t.clientX : e.clientX;
                            const clientY = t ? t.clientY : e.clientY;

                            return { x: clientX - r.left, y: clientY - r.top };
                        },
                        start(e) {
                            if (e.pointerType === 'mouse' && e.button !== 0) {
                                return;
                            }
                            e.preventDefault();
                            if (!this.ctx) {
                                this.initPad();
                            }
                            try {
                                e.currentTarget.setPointerCapture(e.pointerId);
                            } catch (err) {}
                            this.drawing = true;
                            const p = this.pos(e);
                            this.lastX = p.x;
                            this.lastY = p.y;
                        },
                        draw(e) {
                            if (!this.drawing || !this.ctx) {
                                return;
                            }
                            e.preventDefault();
                            const p = this.pos(e);
                            this.ctx.beginPath();
                            this.ctx.moveTo(this.lastX, this.lastY);
                            this.ctx.lineTo(p.x, p.y);
                            this.ctx.stroke();
                            this.lastX = p.x;
                            this.lastY = p.y;
                            this.hasInk = true;
                        },
                        end(e) {
                            this.drawing = false;
                            if (e && e.currentTarget && e.pointerId != null) {
                                try {
                                    e.currentTarget.releasePointerCapture(e.pointerId);
                                } catch (err) {}
                            }
                        },
                        clearPad() {
                            this.initPad();
                        },
                        async submitDocuments(lw) {
                            const url =
                                this.hasInk && this.$refs.sigCanvas
                                    ? this.$refs.sigCanvas.toDataURL('image/png')
                                    : '';
                            await lw.call('finishDocuments', url);
                        },
                    });
                    const register = () => window.Alpine.data('doctorDocumentsStep', factory);

                    // On wire:navigate (SPA) visits `alpine:init` has already fired, so register
                    // immediately when Alpine is present; otherwise wait for the event (hard load).
                    if (window.Alpine) {
                        register();
                    } else {
                        document.addEventListener('alpine:init', register);
                    }
                })();
            </script>
        @endpush
    @endonce

    @php
        $onboardingTitle = match ($step) {
            1 => __('doctor.auth.basic_info_title'),
            2 => __('doctor.auth.professional_title'),
            default => __('doctor.auth.documents_title'),
        };
        $onboardingSubtitle = match ($step) {
            1 => __('doctor.auth.basic_info_subtitle'),
            2 => __('doctor.auth.professional_subtitle'),
            default => __('doctor.auth.documents_subtitle'),
        };
    @endphp

    @include('partials.doctor-onboarding-header', [
        'current' => $step,
        'total' => 6,
        'title' => $onboardingTitle,
        'subtitle' => $onboardingSubtitle,
    ])

    @if ($step === 1)
        <form wire:submit="nextFromBasic" class="doctor-onboarding-form space-y-4">
            <div class="doctor-onboarding-card">
                <div class="border-b border-zinc-200 bg-gradient-to-br from-emerald-50 via-white to-white px-4 py-4 sm:px-6 sm:py-5">
                    <div class="flex items-center gap-3.5 sm:gap-4">
                        <label class="group relative flex size-16 shrink-0 cursor-pointer items-center justify-center rounded-full border-2 border-[#047857]/20 bg-white shadow-[0_8px_24px_-10px_rgba(4,120,87,0.45)] ring-4 ring-[#047857]/10 transition hover:ring-[#047857]/20 sm:size-20">
                            <input
                                type="file"
                                wire:model.live="profile_photo"
                                accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                                class="sr-only"
                            />

                            <span class="contents" wire:loading.remove wire:target="profile_photo">
                                @if ($profile_photo)
                                    <img src="{{ $profile_photo->temporaryUrl() }}" alt="" class="size-full rounded-full object-cover" />
                                @elseif (filled($this->doctor()->profile_photo_path))
                                    <img src="{{ $this->doctor()->profilePhotoUrl() }}" alt="" class="size-full rounded-full object-cover" />
                                @else
                                    <flux:icon name="camera" variant="outline" class="size-6 text-[#047857] sm:size-7" />
                                @endif
                            </span>

                            <span
                                class="absolute inset-0 flex items-center justify-center rounded-full bg-white/80 backdrop-blur-[1px]"
                                wire:loading
                                wire:target="profile_photo"
                            >
                                <flux:icon name="arrow-path" variant="solid" class="size-5 animate-spin text-[#047857]" />
                            </span>

                            <span class="absolute -bottom-0.5 -end-0.5 flex size-7 items-center justify-center rounded-full border-2 border-white bg-[#047857] text-white shadow-sm sm:size-8">
                                <flux:icon name="plus" variant="mini" class="size-3.5 text-white sm:size-4" />
                            </span>
                        </label>

                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-zinc-900">
                                {{ __('doctor.auth.profile_photo_label') }}
                                @if (! filled($this->doctor()->profile_photo_path))
                                    @include('partials.required-field-mark')
                                @endif
                            </p>
                            <p class="mt-0.5 text-[0.6875rem] leading-relaxed text-zinc-600 sm:text-xs">
                                {{ __('doctor.auth.profile_photo_help') }}
                            </p>
                            <flux:error name="profile_photo" />
                        </div>
                    </div>
                </div>

                <div class="space-y-4 px-4 py-4 sm:space-y-5 sm:px-6 sm:py-5">
                    <div class="grid gap-3.5 sm:gap-4">
                        <flux:field>
                            <flux:label>{{ __('doctor.auth.name_ar') }} @include('partials.required-field-mark')</flux:label>
                            <flux:input wire:model="name_ar" dir="rtl" required class="rounded-2xl!" />
                            <flux:error name="name_ar" />
                        </flux:field>
                        <flux:field>
                            <flux:label>{{ __('doctor.auth.name') }} @include('partials.required-field-mark')</flux:label>
                            <flux:input wire:model="name" required class="rounded-2xl!" />
                            <flux:error name="name" />
                        </flux:field>
                    </div>

                    <div class="h-px bg-zinc-200" aria-hidden="true"></div>

                    <div class="grid gap-3.5 sm:gap-4">
                        <flux:field>
                            <flux:label>{{ __('doctor.auth.about_ar') }} @include('partials.required-field-mark')</flux:label>
                            <flux:textarea wire:model="about_ar" rows="2" dir="rtl" required class="rounded-2xl! sm:min-h-24" />
                            <flux:error name="about_ar" />
                        </flux:field>
                        <flux:field>
                            <flux:label>{{ __('doctor.auth.about') }} @include('partials.required-field-mark')</flux:label>
                            <flux:textarea wire:model="about" rows="2" required class="rounded-2xl! sm:min-h-24" />
                            <flux:error name="about" />
                        </flux:field>
                    </div>
                </div>
            </div>

            <flux:button
                class="doctor-onboarding-cta"
                type="submit"
                variant="primary"
                wire:loading.attr="disabled"
                wire:target="profile_photo,nextFromBasic"
            >
                <span wire:loading wire:target="profile_photo">{{ __('doctor.auth.profile_photo_uploading') }}</span>
                <span wire:loading.remove wire:target="profile_photo">{{ __('doctor.auth.continue') }}</span>
            </flux:button>
        </form>
    @elseif ($step === 2)
        @if ($this->degrees->isEmpty() || $this->specialities->isEmpty())
            <flux:callout variant="warning" icon="exclamation-circle">
                {{ __('doctor.auth.catalog_missing_hint') }}
            </flux:callout>
        @endif

        <form wire:submit="nextFromProfessional" class="doctor-emerald-accent doctor-onboarding-form space-y-4">
            <div class="doctor-onboarding-card">
                <div class="space-y-5 px-4 py-4 sm:space-y-6 sm:px-6 sm:py-5">
                    <flux:field>
                        <flux:label>{{ __('doctor.auth.gender') }} @include('partials.required-field-mark')</flux:label>
                        <div class="doctor-emerald-pill-radios doctor-emerald-pill-radios--thirds">
                            <flux:radio.group variant="pills" wire:model.live="gender" class="flex flex-wrap gap-2">
                                <flux:radio value="male" :label="__('doctor.auth.gender_male')" />
                                <flux:radio value="female" :label="__('doctor.auth.gender_female')" />
                                <flux:radio value="other" :label="__('doctor.auth.gender_other')" />
                            </flux:radio.group>
                        </div>
                        <flux:error name="gender" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('doctor.auth.degree') }} @include('partials.required-field-mark')</flux:label>
                        <div class="doctor-emerald-pill-radios">
                            <flux:radio.group variant="pills" wire:model.live="degree_id" class="flex flex-wrap gap-2">
                                @foreach ($this->degrees as $degree)
                                    <flux:radio
                                        value="{{ $degree->id }}"
                                        :label="$localeIsAr && filled($degree->title_ar) ? $degree->title_ar : $degree->title"
                                    />
                                @endforeach
                            </flux:radio.group>
                        </div>
                        <flux:error name="degree_id" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('doctor.auth.specialities') }} @include('partials.required-field-mark')</flux:label>
                        <flux:checkbox.group
                            wire:model.live="speciality_ids"
                            class="doctor-emerald-accent doctor-onboarding-specialities"
                        >
                            @foreach ($this->specialities as $speciality)
                                <label class="min-w-0">
                                    <flux:checkbox value="{{ $speciality->id }}" class="mt-0.5 shrink-0" />
                                    <span class="min-w-0 break-words">
                                        {{ $localeIsAr && filled($speciality->title_ar) ? $speciality->title_ar : $speciality->title }}
                                    </span>
                                </label>
                            @endforeach
                        </flux:checkbox.group>
                        <flux:error name="speciality_ids" />
                    </flux:field>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>{{ __('doctor.auth.registration_number') }} @include('partials.required-field-mark')</flux:label>
                            <flux:input wire:model="registration_number" autocomplete="off" class="rounded-2xl!" />
                            <flux:error name="registration_number" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('doctor.auth.experience_years') }} @include('partials.required-field-mark')</flux:label>
                            <flux:input wire:model.live="experience" type="number" min="0" max="80" class="rounded-2xl!" />
                            <flux:error name="experience" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>{{ __('doctor.auth.medical_career_level') }}</flux:label>
                        <flux:input wire:model="medical_career_level" :placeholder="__('doctor.auth.optional')" class="rounded-2xl!" />
                        <flux:error name="medical_career_level" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('doctor.auth.spoken_languages') }} @include('partials.required-field-mark')</flux:label>
                        <div class="doctor-emerald-pill-radios doctor-emerald-pill-radios--thirds">
                            <flux:radio.group variant="pills" wire:model.live="spoken_languages" class="flex flex-wrap gap-2">
                                <flux:radio value="ar" :label="__('doctor.auth.lang_ar')" />
                                <flux:radio value="en" :label="__('doctor.auth.lang_en')" />
                                <flux:radio value="ar_en" :label="__('doctor.auth.lang_ar_en')" />
                            </flux:radio.group>
                        </div>
                        <flux:error name="spoken_languages" />
                    </flux:field>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-between">
                <flux:button type="button" variant="ghost" wire:click="goBack" class="order-2 !rounded-full !text-zinc-700 sm:order-1">
                    {{ __('doctor.auth.back') }}
                </flux:button>
                <flux:button
                    class="doctor-onboarding-cta order-1 sm:order-2 sm:w-auto sm:min-w-40"
                    type="submit"
                    variant="primary"
                >
                    {{ __('doctor.auth.continue') }}
                </flux:button>
            </div>
        </form>
    @else
        <form
            class="doctor-onboarding-form space-y-4"
            wire:key="doctor-onboarding-documents-step"
            x-data="doctorDocumentsStep()"
            x-init="initPad()"
            x-on:submit.prevent="submitDocuments($wire)"
        >
            <div class="doctor-onboarding-card">
                <div class="space-y-5 px-4 py-4 sm:space-y-6 sm:px-6 sm:py-5">
                    <flux:field>
                        <flux:label>{{ __('doctor.auth.certificate_label') }} @include('partials.required-field-mark')</flux:label>
                        <flux:text class="text-sm text-zinc-600">{{ __('doctor.auth.certificate_help') }}</flux:text>
                        <div class="relative mt-2">
                            <input
                                type="file"
                                wire:model="certificate"
                                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
                            />
                            <div class="flex flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-zinc-300 bg-zinc-50 px-5 py-7 text-center transition hover:border-[#047857]/60 hover:bg-emerald-50/50">
                                <flux:icon name="arrow-up-tray" class="size-7 text-[#047857]" />
                                <flux:text class="text-sm font-semibold text-zinc-800">{{ __('doctor.auth.certificate_dropzone_heading') }}</flux:text>
                                <flux:text class="text-xs text-zinc-600">{{ __('doctor.auth.certificate_help') }}</flux:text>
                            </div>
                        </div>

                        <div wire:loading wire:target="certificate" class="mt-2 text-sm font-medium text-zinc-600">
                            {{ __('doctor.auth.upload_preparing') }}
                        </div>

                        @if ($certificate)
                            @php
                                $certMime = (string) ($certificate->getMimeType() ?? '');
                                $certIsImage = str_starts_with($certMime, 'image/');
                            @endphp
                            <div
                                class="mt-3 flex items-center gap-3 rounded-2xl border border-zinc-300 bg-white px-3 py-2.5 shadow-sm"
                                wire:loading.remove
                                wire:target="certificate"
                            >
                                @if ($certIsImage)
                                    <img src="{{ $certificate->temporaryUrl() }}" alt="" class="size-11 shrink-0 rounded-lg object-cover" />
                                @else
                                    <span class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-[#047857]/10 text-[#047857]">
                                        <flux:icon name="document-text" class="size-6" />
                                    </span>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <flux:text class="truncate text-sm font-semibold text-zinc-900">
                                        {{ $certificate->getClientOriginalName() }}
                                    </flux:text>
                                    <flux:text class="text-xs text-zinc-600">
                                        {{ \Illuminate\Support\Number::fileSize((int) $certificate->getSize()) }}
                                    </flux:text>
                                </div>
                                <flux:button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    icon="x-mark"
                                    wire:click="removeCertificate"
                                    :aria-label="__('doctor.auth.certificate_remove')"
                                />
                            </div>
                        @endif

                        <flux:error name="certificate" />
                    </flux:field>

                    <div class="h-px bg-zinc-200" aria-hidden="true"></div>

                    <flux:field>
                        <flux:label>{{ __('doctor.auth.signature_label') }} @include('partials.required-field-mark')</flux:label>
                        <flux:text class="text-sm text-zinc-600">{{ __('doctor.auth.signature_help') }}</flux:text>
                        <div wire:ignore class="mt-2 space-y-2">
                            <div class="overflow-hidden rounded-2xl border-2 border-dashed border-zinc-300 bg-zinc-50 p-1">
                                <canvas
                                    x-ref="sigCanvas"
                                    class="block h-44 min-h-44 w-full min-w-0 cursor-crosshair touch-none bg-white"
                                    style="touch-action: none"
                                    x-on:pointerdown="start($event)"
                                    x-on:pointermove="draw($event)"
                                    x-on:pointerup="end($event)"
                                    x-on:pointerleave="end($event)"
                                ></canvas>
                            </div>
                            <flux:button type="button" variant="ghost" size="sm" class="w-full !text-zinc-700 sm:w-auto" x-on:click="clearPad()">
                                {{ __('doctor.auth.signature_clear') }}
                            </flux:button>
                        </div>
                        <flux:error name="signatureDataUrl" />
                    </flux:field>
                </div>
            </div>

            <div wire:loading wire:target="certificate,finishOnboarding" class="text-sm font-medium text-zinc-600">
                {{ __('doctor.auth.upload_preparing') }}
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-between">
                <flux:button type="button" variant="ghost" wire:click="goBack" class="order-2 !rounded-full !text-zinc-700 sm:order-1">
                    {{ __('doctor.auth.back') }}
                </flux:button>
                <flux:button
                    class="doctor-onboarding-cta order-1 sm:order-2 sm:w-auto sm:min-w-44"
                    type="submit"
                    variant="primary"
                    wire:loading.attr="disabled"
                    wire:target="certificate,finishOnboarding"
                >
                    {{ __('doctor.auth.save_continue') }}
                </flux:button>
            </div>
        </form>
    @endif
</div>
