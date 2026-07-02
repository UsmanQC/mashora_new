<!DOCTYPE html>
<html lang="ar" dir="rtl" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Awaan | The Premium Health Platform</title>

    <link rel="icon" href="{{ asset('images/favicon-awaan.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('images/favicon-awaan.png') }}">
    
    <link rel="stylesheet" href="{{ asset('fonts/thmanyah/thmanyah.css') }}">
    
    <!-- Premium Icons: Lucide -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Thmanyah Sans', 'sans-serif'],
                        display: ['Thmanyah Sans', 'sans-serif'],
                        thamanyah: ['Thmanyah Sans', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            DEFAULT: '#10B981', // The mandated Emerald Green
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        },
                        surface: {
                            DEFAULT: '#FFFFFF',
                            subtle: '#F8FAFC', // Slate 50 - Premium soft gray
                            muted: '#F1F5F9'   // Slate 100
                        },
                        ink: {
                            DEFAULT: '#0F172A', // Slate 900 - Softer than pure black
                            subtle: '#334155',  // Slate 700
                            muted: '#64748B'    // Slate 500
                        }
                    },
                    boxShadow: {
                        'premium': '0 10px 40px -10px rgba(16, 185, 129, 0.08), 0 20px 50px -10px rgba(0, 0, 0, 0.03)',
                        'float': '0 20px 40px -8px rgba(0, 0, 0, 0.04)',
                        'card-hover': '0 20px 40px -12px rgba(0, 0, 0, 0.06)',
                        'inner-light': 'inset 0 1px 0 0 rgba(255, 255, 255, 0.8)',
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                        'blob': 'blob 15s infinite alternate',
                        'pulse-slow': 'pulseSlow 3s infinite',
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(30px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(40px, -50px) scale(1.05)' },
                            '66%': { transform: 'translate(-30px, 30px) scale(0.95)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' }
                        },
                        pulseSlow: {
                            '0%, 100%': { opacity: '1', transform: 'scale(1)' },
                            '50%': { opacity: '0.85', transform: 'scale(1.05)' }
                        }
                    }
                }
            }
        }
    </script>

    <style>
        /* Base Resets & Typography Refinements */
        html {
            overflow-x: clip;
            max-width: 100%;
        }

        body {
            background-color: #FFFFFF;
            color: #0F172A;
            font-family: 'Thmanyah Sans', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Thmanyah Sans', sans-serif;
            letter-spacing: -0.015em;
        }

        /* Enforce ultra-premium thin strokes for all Lucide Icons */
        .lucide {
            stroke-width: 1.5;
            vector-effect: non-scaling-stroke;
        }

        /* Glassmorphism Utilities */
        .glass-nav {
            background: rgba(255, 255, 255, 0.80);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(15, 23, 42, 0.03);
        }

        .home-site-header {
            padding-top: env(safe-area-inset-top);
        }

        .home-site-header__inner {
            padding-top: 1.125rem;
            padding-bottom: 1.125rem;
        }

        .home-main {
            padding-top: calc(5.625rem + env(safe-area-inset-top));
        }

        @media (min-width: 640px) {
            .home-site-header__inner {
                padding-top: 1.25rem;
                padding-bottom: 1.25rem;
            }

            .home-main {
                padding-top: calc(6rem + env(safe-area-inset-top));
            }
        }

        @media (min-width: 768px) {
            .home-site-header__inner {
                padding-top: 1.375rem;
                padding-bottom: 1.375rem;
            }

            .home-main {
                padding-top: calc(6.5rem + env(safe-area-inset-top));
            }
        }

        /* Emotion intake cards */
        .emotion-card {
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .emotion-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.05), 0 0 0 1px rgba(16, 185, 129, 0.1);
            background-color: #FFFFFF;
        }
        .emotion-card.active {
            background: #10B981;
            color: white;
            box-shadow: 0 20px 40px -12px rgba(16, 185, 129, 0.3);
            border-color: transparent;
        }
        .emotion-card.active p { color: rgba(255, 255, 255, 0.9); }
        .emotion-card.active h3 { color: white; }
        .emotion-card.active .icon-box { background: rgba(255, 255, 255, 0.2); color: white; border-color: transparent; }
        .emotion-card.active .icon-box svg { stroke: white; }

        /* Hide scrollbars for clean horizontal scrolling */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Auto-moving doctors row — no arrows, no scrollbar */
        .doctors-marquee {
            overflow: hidden;
            -webkit-mask-image: linear-gradient(to right, transparent, #000 4%, #000 96%, transparent);
            mask-image: linear-gradient(to right, transparent, #000 4%, #000 96%, transparent);
        }

        .doctors-marquee-track {
            display: flex;
            width: max-content;
            gap: 2rem;
            will-change: transform;
            animation: doctors-marquee 50s linear infinite;
        }

        .doctors-marquee:hover .doctors-marquee-track {
            animation-play-state: paused;
        }

        @keyframes doctors-marquee {
            from { transform: translate3d(0, 0, 0); }
            to { transform: translate3d(-50%, 0, 0); }
        }

        @media (prefers-reduced-motion: reduce) {
            .doctors-marquee {
                overflow-x: auto;
                -webkit-mask-image: none;
                mask-image: none;
            }

            .doctors-marquee-track {
                animation: none;
            }

            .marketing-appt-card,
            .marketing-appointments > div[class*="animate-fade-in-up"] {
                animation: none !important;
                opacity: 1 !important;
            }
        }

        /* Ambient Background Mesh - Slower and softer */
        .bg-mesh {
            position: fixed;
            inset: 0;
            width: 100%;
            overflow: hidden;
            z-index: -1;
            pointer-events: none;
        }
        .bg-mesh-blob {
            position: absolute;
            filter: blur(100px);
            opacity: 0.3;
            border-radius: 50%;
        }
    </style>
</head>
<body class="relative">

    <div class="bg-mesh">
        <div class="bg-mesh-blob bg-primary-100 w-[600px] h-[600px] top-[-10%] right-[-10%] animate-blob"></div>
        <div class="bg-mesh-blob bg-primary-50 w-[500px] h-[500px] top-[20%] left-[-10%] animate-blob" style="animation-delay: 3s;"></div>
        <div class="bg-mesh-blob bg-emerald-50 w-[700px] h-[700px] top-[60%] right-[10%] animate-blob" style="animation-delay: 6s;"></div>
    </div>

    <header class="home-site-header fixed top-0 w-full z-50 glass-nav transition-all duration-300">
        <div class="home-site-header__inner max-w-[85rem] mx-auto flex items-center justify-between px-5 sm:px-6">
            
            <!-- Logo -->
            <a href="{{ route('home') }}" class="inline-flex items-center cursor-pointer" title="Awaan">
                @include('partials.patient-brand-logo', ['svgClass' => 'h-10 w-auto max-w-[min(100%,11rem)]'])
            </a>

            <!-- Desktop Menu -->
            <nav class="hidden md:flex items-center gap-12">
                <a href="#" class="text-sm font-medium text-primary-600 transition-colors">الرئيسية</a>
                <a href="{{ route('patient.schedule.specialists') }}" class="text-sm font-medium text-ink-muted hover:text-ink transition-colors">المختصون</a>
                <a href="#" class="text-sm font-medium text-ink-muted hover:text-ink transition-colors">المقاييس النفسية</a>
                <a href="#" class="text-sm font-medium text-ink-muted hover:text-ink transition-colors">للأعمال</a>
                <button
                    type="button"
                    data-open-ai-chatbot
                    class="inline-flex items-center gap-2 text-sm font-medium text-primary transition-colors hover:text-primary-700"
                >
                    <i data-lucide="bot" class="h-4 w-4"></i>
                    المساعد الذكي
                </button>
            </nav>

            <!-- Actions -->
            <div class="flex items-center gap-3 sm:gap-6">
                <a
                    href="{{ route('patient.phone') }}"
                    class="inline-flex items-center gap-2 rounded-2xl border border-primary/20 bg-primary/5 px-4 py-2.5 text-sm font-semibold leading-none text-primary transition hover:bg-primary/10 md:hidden"
                >
                    تسجيل الدخول
                </a>
                <a href="{{ route('patient.phone') }}" class="hidden md:inline-flex text-sm font-medium text-ink-muted transition-colors hover:text-ink px-2">تسجيل الدخول</a>
            </div>
        </div>
    </header>

    <main class="home-main relative px-4 pb-16 sm:px-6 md:pb-24">
        <div class="max-w-[85rem] mx-auto">
            
            <div class="grid lg:grid-cols-12 gap-10 lg:gap-20 items-center">
                
                <!-- Left Content: Value Proposition -->
                <div class="lg:col-span-5 text-right opacity-0 animate-fade-in-up">
    
                    <h1 class="text-4xl sm:text-5xl lg:text-[4.5rem] leading-[1.15] font-display font-bold text-ink mb-6 sm:mb-8 tracking-tight">
                        صحتك النفسية،<br>تبدأ <span class="text-primary">بحديث.</span>
                    </h1>
                    
                    <p class="text-base sm:text-lg text-ink-muted leading-relaxed mb-0 sm:mb-2 max-w-lg font-sans">
                        نحن لا نقدم مجرد استشارات، بل نوفر ملاذاً آمناً يجمعك بنخبة من المعالجين النفسيين المرخصين. بخصوصية تامة، وفي الوقت الذي تحتاجه.
                    </p>
                </div>

                <!-- Right: emotion intake widget (desktop) -->
                <div class="hidden lg:block lg:col-span-7 relative opacity-0 animate-fade-in-up" style="animation-delay: 0.15s;">
                    @include('partials.marketing-emotion-widget')
                </div>

            </div>

            <!-- Appointments -->
            <div class="mt-8 max-w-md mx-auto sm:max-w-none sm:mt-10 lg:mt-14 opacity-0 animate-fade-in-up" style="animation-delay: 0.18s;">
                @include('partials.marketing-appointment-cards')
            </div>

            <!-- Emotion intake widget (mobile & tablet) -->
            <div class="mt-8 max-w-md mx-auto sm:max-w-none lg:hidden opacity-0 animate-fade-in-up" style="animation-delay: 0.24s;">
                @include('partials.marketing-emotion-widget')
            </div>

        </div>
    </main>

    <section class="py-12 border-y border-slate-100 bg-surface-subtle">
        <div class="max-w-[85rem] mx-auto px-6">
            <p class="text-center text-sm font-medium text-ink-muted mb-10 tracking-wide">مرخصون ومعتمدون من أبرز الجهات الصحية</p>
            <div class="flex flex-wrap justify-center gap-16 md:gap-28 opacity-50 grayscale hover:grayscale-0 transition-all duration-700">
                <div class="flex items-center gap-3 font-display font-bold text-xl text-ink"><i data-lucide="heart-pulse" class="w-8 h-8 text-ink"></i> وزارة الصحة</div>
                <div class="flex items-center gap-3 font-display font-bold text-xl text-ink"><i data-lucide="shield-check" class="w-8 h-8 text-ink"></i> المركز الوطني</div>
                <div class="flex items-center gap-3 font-display font-bold text-xl text-ink"><i data-lucide="lock" class="w-8 h-8 text-ink"></i> HIPAA Compliant</div>
                <div class="flex items-center gap-3 font-display font-bold text-xl text-ink"><i data-lucide="cross" class="w-8 h-8 text-ink"></i> هيئة التخصصات</div>
            </div>
        </div>
    </section>

    <section class="py-40 bg-surface">
        <div class="max-w-[85rem] mx-auto px-6">
            
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-20 gap-8">
                <div>
                    <h2 class="text-4xl lg:text-5xl font-display font-bold text-ink mb-6 tracking-tight">نخبة من المختصين<br>في متناول يدك.</h2>
                    <p class="text-ink-muted text-lg max-w-xl leading-relaxed">تم اختيار معالجينا بعناية فائقة. أطباء وأخصائيون مرخصون بخبرات تتجاوز 10 سنوات، مستعدون للاستماع إليك.</p>
                </div>
                <a href="{{ route('patient.schedule.specialists') }}" class="inline-flex items-center gap-2 text-primary font-bold hover:text-primary-700 transition-colors bg-primary-50 px-7 py-3.5 rounded-full">
                    عرض جميع المختصين <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
            </div>

            @if (count($featuredDoctors) >= 2)
                <div class="doctors-marquee pb-16 pt-4" dir="ltr" data-home-doctors-marquee>
                    <div class="doctors-marquee-track">
                        @foreach ($featuredDoctors as $doctor)
                            @include('partials.marketing-doctor-card', ['doctor' => $doctor])
                        @endforeach
                        @foreach ($featuredDoctors as $doctor)
                            <div aria-hidden="true">
                                @include('partials.marketing-doctor-card', ['doctor' => $doctor])
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="flex gap-8 overflow-x-auto overflow-y-hidden pb-16 pt-4 no-scrollbar">
                    @forelse ($featuredDoctors as $doctor)
                        @include('partials.marketing-doctor-card', ['doctor' => $doctor])
                    @empty
                        <div class="min-w-full rounded-[2rem] border border-dashed border-slate-200 bg-surface-subtle px-8 py-16 text-center">
                            <p class="text-lg font-bold text-ink mb-2">المختصون قريباً</p>
                            <p class="text-sm text-ink-muted mb-6">نعمل على إضافة نخبة من المعالجين المرخصين إلى المنصة.</p>
                            <a href="{{ route('patient.phone') }}" class="inline-flex items-center gap-2 rounded-full bg-primary px-6 py-3 text-sm font-bold text-white transition hover:bg-primary-600">
                                انضم كمريض
                            </a>
                        </div>
                    @endforelse
                </div>
            @endif
        </div>
    </section>

    <section class="py-40 bg-surface-subtle overflow-hidden border-t border-slate-100">
        <div class="max-w-[85rem] mx-auto px-6">
            <div class="grid lg:grid-cols-2 gap-24 items-center">
                
                <!-- Left: Text & Steps -->
                <div>
                    <h2 class="text-4xl lg:text-5xl font-display font-bold text-ink mb-8 tracking-tight">مصمم لراحتك.<br>مبني على الخصوصية.</h2>
                    <p class="text-ink-muted text-lg mb-14 leading-relaxed max-w-lg">تخلصنا من كل التعقيدات التقنية لنجعل وصولك للرعاية النفسية سهلاً، آمناً، ويحترم مساحتك الشخصية لأبعد الحدود.</p>

                    <div class="space-y-10">
                        <div class="flex gap-6 items-start">
                            <div class="w-14 h-14 rounded-2xl bg-white shadow-sm flex items-center justify-center flex-shrink-0 text-primary border border-slate-100">
                                <i data-lucide="lock" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <h4 class="font-display font-bold text-ink text-xl mb-2">سرية تامة وتشفير كامل</h4>
                                <p class="text-ink-muted text-sm leading-relaxed max-w-md">كافة جلساتك ومحادثاتك مشفرة من الطرفين. لا أحد يستطيع الاطلاع عليها، ولا يتم تسجيلها أبداً.</p>
                            </div>
                        </div>
                        <div class="flex gap-6 items-start">
                            <div class="w-14 h-14 rounded-2xl bg-white shadow-sm flex items-center justify-center flex-shrink-0 text-primary border border-slate-100">
                                <i data-lucide="shield-check" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <h4 class="font-display font-bold text-ink text-xl mb-2">هوية مجهولة (اختياري)</h4>
                                <p class="text-ink-muted text-sm leading-relaxed max-w-md">لست مضطراً للإفصاح عن هويتك الحقيقية. يمكنك التسجيل باسم مستعار والحصول على الرعاية براحة تامة.</p>
                            </div>
                        </div>
                        <div class="flex gap-6 items-start">
                            <div class="w-14 h-14 rounded-2xl bg-white shadow-sm flex items-center justify-center flex-shrink-0 text-primary border border-slate-100">
                                <i data-lucide="video" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <h4 class="font-display font-bold text-ink text-xl mb-2">مرونة في التواصل</h4>
                                <p class="text-ink-muted text-sm leading-relaxed max-w-md">اختر الطريقة التي تريحك: مكالمة فيديو، مكالمة صوتية، أو حتى رسائل نصية فقط.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Custom UI Component Showcase (Refined borders & soft shadows) -->
                <div class="relative h-[650px] bg-white rounded-[3rem] shadow-premium border border-slate-100 p-8 flex flex-col justify-between">
                    
                    <div class="flex justify-between items-center mb-8 px-2">
                        <div class="flex items-center gap-3">
                            <div class="w-2.5 h-2.5 bg-red-500 rounded-full animate-pulse"></div>
                            <span class="text-sm font-bold text-ink tracking-wide">جلسة جارية</span>
                        </div>
                        <span class="bg-surface-subtle text-ink-subtle px-4 py-1.5 rounded-full text-xs font-bold font-mono border border-slate-100">04:22</span>
                    </div>

                    <!-- Video UI Mockup -->
                    <div class="flex-1 bg-ink rounded-3xl relative overflow-hidden mb-8 shadow-inner">
                        <img src="https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=800&h=600&fit=crop" alt="Video Call" class="object-cover w-full h-full opacity-80 mix-blend-luminosity">
                        <!-- Controls -->
                        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-4 bg-white/10 backdrop-blur-md px-6 py-3 rounded-full border border-white/10">
                            <div class="w-11 h-11 rounded-full bg-white/20 flex items-center justify-center text-white hover:bg-white/30 transition-colors cursor-pointer"><i data-lucide="mic" class="w-5 h-5"></i></div>
                            <div class="w-11 h-11 rounded-full bg-white/20 flex items-center justify-center text-white hover:bg-white/30 transition-colors cursor-pointer"><i data-lucide="video" class="w-5 h-5"></i></div>
                            <div class="w-11 h-11 rounded-full bg-red-500 flex items-center justify-center text-white hover:bg-red-600 transition-colors cursor-pointer"><i data-lucide="phone-off" class="w-5 h-5"></i></div>
                        </div>
                    </div>

                    <!-- Chat Bubble Mockup -->
                    <div class="bg-surface-subtle border border-slate-100 p-5 rounded-2xl flex gap-4 items-start shadow-sm">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0"><i data-lucide="bot" class="w-5 h-5"></i></div>
                        <div class="pt-0.5">
                            <p class="text-sm text-ink font-bold mb-1.5">المساعد الذكي</p>
                            <p class="text-xs text-ink-muted leading-relaxed">تم إرسال التقييم الأسبوعي بنجاح. نتمنى لك يوماً هادئاً وخالياً من الضغوط.</p>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </section>

    <footer class="bg-ink pt-28 pb-12 rounded-t-[3rem] mt-[-2rem] relative z-10">
        <div class="max-w-[85rem] mx-auto px-6">
            
            <div class="bg-primary rounded-[3rem] p-14 md:p-20 flex flex-col md:flex-row items-center justify-between gap-10 mb-28 relative overflow-hidden shadow-2xl">
                <!-- Decorative elements inside CTA -->
                <div class="absolute top-0 right-0 w-80 h-80 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 w-80 h-80 bg-black/10 rounded-full blur-3xl pointer-events-none"></div>
                
                <div class="relative z-10 text-center md:text-right">
                    <h2 class="text-4xl md:text-5xl font-display font-bold text-white mb-5 tracking-tight">مستعد لبدء رحلة التعافي؟</h2>
                    <p class="text-primary-50 text-lg font-medium max-w-xl">حمل التطبيق الآن واحصل على دعم نفسي متكامل بين يديك، بخصوصية وسهولة مطلقة.</p>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-5 relative z-10 w-full md:w-auto">
                    <button class="bg-white hover:bg-surface-subtle text-ink px-8 py-4 rounded-2xl font-bold flex items-center justify-center gap-4 transition-colors min-w-[220px] shadow-lg">
                        <i data-lucide="apple" class="w-7 h-7"></i>
                        <div class="text-right leading-none">
                            <span class="text-[10px] text-ink-muted block mb-1 font-sans">Download on the</span>
                            <span class="text-base tracking-tight">App Store</span>
                        </div>
                    </button>
                    <button class="bg-ink hover:bg-slate-800 text-white px-8 py-4 rounded-2xl font-bold flex items-center justify-center gap-4 transition-colors min-w-[220px] shadow-lg border border-slate-700">
                        <i data-lucide="play" class="w-7 h-7"></i>
                        <div class="text-right leading-none">
                            <span class="text-[10px] text-slate-400 block mb-1 font-sans">GET IT ON</span>
                            <span class="text-base tracking-tight">Google Play</span>
                        </div>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-16 border-b border-slate-800 pb-16 mb-12">
                <div class="col-span-2 md:col-span-1">
                    <a href="{{ route('home') }}" class="inline-flex items-center mb-8" title="Awaan">
                        @include('partials.patient-brand-logo', ['svgClass' => 'h-10 w-auto max-w-[min(100%,11rem)]'])
                    </a>
                    <p class="text-sm text-slate-400 leading-relaxed max-w-xs">المنصة الرائدة للرعاية النفسية المتكاملة، بخصوصية وأمان، ومقاييس عالمية.</p>
                </div>
                
                <div>
                    <h4 class="text-white font-bold mb-8 tracking-wide">المنصة</h4>
                    <ul class="space-y-5 text-sm text-slate-400 font-medium">
                        <li><a href="#" class="hover:text-primary-400 transition-colors">من نحن</a></li>
                        <li><a href="#" class="hover:text-primary-400 transition-colors">تصفح الخبراء</a></li>
                        <li><a href="#" class="hover:text-primary-400 transition-colors">الأسئلة الشائعة</a></li>
                        <li><a href="#" class="hover:text-primary-400 transition-colors">تواصل معنا</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-bold mb-8 tracking-wide">الخدمات</h4>
                    <ul class="space-y-5 text-sm text-slate-400 font-medium">
                        <li><a href="#" class="hover:text-primary-400 transition-colors">استشارة فورية</a></li>
                        <li><a href="#" class="hover:text-primary-400 transition-colors">جلسات مجدولة</a></li>
                        <li><a href="#" class="hover:text-primary-400 transition-colors">المقاييس النفسية</a></li>
                        <li><a href="#" class="hover:text-primary-400 transition-colors">برامج الشركات</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-bold mb-8 tracking-wide">انضم إلينا</h4>
                    <ul class="space-y-5 text-sm text-slate-400 font-medium">
                        <li><a href="#" class="hover:text-primary-400 transition-colors">سجل كمختص</a></li>
                        <li><a href="#" class="hover:text-primary-400 transition-colors">دليل المعالجين</a></li>
                        <li><a href="#" class="hover:text-primary-400 transition-colors">سياسة الاستخدام</a></li>
                    </ul>
                </div>
            </div>

            <div class="flex flex-col md:flex-row justify-between items-center text-sm text-slate-500 font-medium">
                <p>&copy; 2026 Awaan. All rights reserved.</p>
                <div class="flex gap-8 mt-6 md:mt-0">
                    <a href="#" class="hover:text-white transition-colors">الشروط والأحكام</a>
                    <a href="#" class="hover:text-white transition-colors">سياسة الخصوصية</a>
                </div>
            </div>
        </div>
    </footer>

    @include('partials.ai-chatbot-widget', [
        'forceVisible' => true,
        'hideToggle' => false,
        'toggleAnchor' => 'left',
        'layout' => 'corner',
    ])

    <!-- Interactive Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();

            document.querySelectorAll('.emotion-card').forEach((card) => {
                card.addEventListener('click', () => {
                    card.closest('.marketing-emotion-widget')?.querySelectorAll('.emotion-card').forEach((item) => {
                        item.classList.remove('active');
                    });
                    card.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>