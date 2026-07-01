@props([
    /** @var array<string, mixed> $doctor */
    'doctor',
])

@php
    $photoUrl = $doctor['photo_url'] ?? null;
    $avatarFallback = 'https://ui-avatars.com/api/?name='.urlencode((string) ($doctor['name'] ?? 'Awaan')).'&background=10B981&color=fff&size=512';
    $imageUrl = filled($photoUrl) ? $photoUrl : $avatarFallback;
    $tags = array_slice((array) ($doctor['tags'] ?? []), 0, 2);
    $channels = (array) ($doctor['channels'] ?? []);
    $hasChat = (bool) ($channels['chat'] ?? false);
    $hasVideo = (bool) ($channels['video'] ?? false);
    $hasVoice = (bool) ($channels['voice'] ?? false);
    $isOnline = (bool) ($doctor['is_online'] ?? false);
    $bookingUrl = route('patient.schedule.specialists');
@endphp

<div class="min-w-[340px] max-w-[340px] bg-surface rounded-[2rem] p-7 border border-slate-100 shadow-sm hover:shadow-card-hover hover:-translate-y-2 transition-all duration-500 snap-center relative group">
    @if ($isOnline)
        <div class="absolute top-7 left-7 flex items-center gap-2 bg-white/90 backdrop-blur-sm px-3.5 py-1.5 rounded-full shadow-sm border border-slate-50 z-10">
            <span class="w-2 h-2 bg-primary rounded-full animate-pulse-slow"></span>
            <span class="text-xs font-bold text-ink">متاح الآن</span>
        </div>
    @endif

    <div class="relative mb-8 rounded-2xl overflow-hidden aspect-square bg-slate-50">
        <img
            src="{{ $imageUrl }}"
            alt="{{ $doctor['name'] }}"
            class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-700 ease-out"
            loading="lazy"
            decoding="async"
        >
        @if (! $isOnline)
            <div class="absolute bottom-5 right-5 bg-white/90 backdrop-blur-sm px-4 py-2 rounded-full text-xs font-bold text-ink shadow-sm">
                احجز موعداً
            </div>
        @endif
    </div>

    <div class="mb-5">
        <div class="flex justify-between items-start mb-3 gap-3">
            <h3 class="font-display font-bold text-xl text-ink">{{ $doctor['name'] }}</h3>
            @if (($doctor['experience_years'] ?? 0) > 0)
                <div class="flex items-center gap-1.5 text-sm font-bold text-ink bg-surface-subtle px-2.5 py-1 rounded-lg shrink-0">
                    <i data-lucide="briefcase" class="w-3.5 h-3.5 text-primary"></i>
                    {{ $doctor['experience_years'] }}+
                </div>
            @endif
        </div>
        @if (filled($doctor['degree_title'] ?? null))
            <p class="text-sm text-primary font-semibold mb-2">{{ $doctor['degree_title'] }}</p>
        @endif
        @if (filled($doctor['bio'] ?? null))
            <p class="text-sm text-ink-muted line-clamp-2 leading-relaxed">{{ $doctor['bio'] }}</p>
        @endif
    </div>

    @if ($tags !== [])
        <div class="flex gap-2 mb-8 flex-wrap">
            @foreach ($tags as $tag)
                <span class="text-xs bg-surface-subtle text-ink-subtle px-3.5 py-1.5 rounded-lg font-medium">{{ $tag }}</span>
            @endforeach
        </div>
    @endif

    @if ($hasChat && $hasVideo)
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ $bookingUrl }}" class="bg-surface-subtle hover:bg-slate-100 text-ink py-3.5 rounded-xl text-sm font-bold transition-colors text-center">محادثة</a>
            <a href="{{ $bookingUrl }}" class="bg-primary hover:bg-primary-600 text-white py-3.5 rounded-xl text-sm font-bold transition-colors text-center">مكالمة فيديو</a>
        </div>
    @elseif ($hasVideo || $hasVoice)
        <a href="{{ $bookingUrl }}" class="block w-full bg-primary hover:bg-primary-600 text-white py-3.5 rounded-xl text-sm font-bold transition-colors text-center">
            {{ $hasVideo ? 'مكالمة فيديو' : 'مكالمة صوتية' }}
        </a>
    @else
        <a href="{{ $bookingUrl }}" class="block w-full bg-ink hover:bg-ink-subtle text-white py-3.5 rounded-xl text-sm font-bold transition-colors text-center">
            جدولة موعد
        </a>
    @endif
</div>
