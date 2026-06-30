<!DOCTYPE html>
<html lang="ar" dir="rtl" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Awaan | The Premium Health Platform</title>

    <link rel="icon" href="{{ asset('images/favicon-awaan.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('images/favicon-awaan.png') }}">
    
    <!-- Typography: Alexandria for Headers, Tajawal for Body (Premium Arabic Pair) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700;800&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- Premium Icons: Lucide -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Tajawal', 'sans-serif'],
                        display: ['Alexandria', 'sans-serif'],
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
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Alexandria', sans-serif;
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

        /* Hide scrollbars for clean horizontal scrolling */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Custom Interactive Elements (Softer & More Elegant) */
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

    <header class="fixed top-0 w-full z-50 glass-nav transition-all duration-300">
        <div class="max-w-[85rem] mx-auto px-6 h-24 flex items-center justify-between">
            
            <!-- Logo -->
            <a href="{{ route('home') }}" class="inline-flex items-center cursor-pointer" title="Awaan">
                @include('partials.patient-brand-logo', ['svgClass' => 'h-10 w-auto max-w-[min(100%,11rem)]'])
            </a>

            <!-- Desktop Menu -->
            <nav class="hidden md:flex items-center gap-12">
                <a href="#" class="text-sm font-medium text-primary-600 transition-colors">الرئيسية</a>
                <a href="#" class="text-sm font-medium text-ink-muted hover:text-ink transition-colors">المختصون</a>
                <a href="#" class="text-sm font-medium text-ink-muted hover:text-ink transition-colors">المقاييس النفسية</a>
                <a href="#" class="text-sm font-medium text-ink-muted hover:text-ink transition-colors">للأعمال</a>
            </nav>

            <!-- Actions -->
            <div class="flex items-center gap-6">
                <a href="{{ route('patient.phone') }}" class="hidden sm:block text-sm font-medium text-ink-muted hover:text-ink px-2 transition-colors">تسجيل الدخول</a>
               
            </div>
        </div>
    </header>

    <main class="relative pt-24 pb-32 md:pt-32 md:pb-40 px-6">
        <div class="max-w-[85rem] mx-auto">
            
            <div class="grid lg:grid-cols-12 gap-20 items-center">
                
                <!-- Left Content: Value Proposition -->
                <div class="lg:col-span-5 text-right opacity-0 animate-fade-in-up">
                    {{-- <div class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-surface-subtle border border-slate-100 text-ink-subtle text-xs font-bold mb-10 shadow-sm">
                        <i data-lucide="shield-check" class="text-primary w-4 h-4"></i> مساحة آمنة وموثوقة 100%
                    </div> --}}
                    
                    <button class="bg-ink hover:bg-ink-subtle text-white px-7 py-3 rounded-2xl text-sm font-medium transition-all shadow-float flex items-center gap-3 group">
                        <span class="relative flex h-2.5 w-2.5">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-primary"></span>
                        </span>
                        جلسة فورية
                    </button>
                    <h1 class="text-5xl lg:text-[4.5rem] leading-[1.15] font-display font-bold text-ink mb-8 tracking-tight">
                        صحتك النفسية،<br>تبدأ <span class="text-primary">بحديث.</span>
                    </h1>
                    
                    <p class="text-lg text-ink-muted leading-relaxed mb-12 max-w-lg font-sans">
                        نحن لا نقدم مجرد استشارات، بل نوفر ملاذاً آمناً يجمعك بنخبة من المعالجين النفسيين المرخصين. بخصوصية تامة، وفي الوقت الذي تحتاجه.
                    </p>

                    <div class="flex items-center gap-10 border-t border-slate-100 pt-10">
                        <div>
                            <p class="text-3xl font-display font-bold text-ink mb-1">4.9</p>
                            <p class="text-sm text-ink-muted flex items-center gap-1.5"><i data-lucide="star" class="text-amber-400 w-4 h-4 fill-amber-400"></i> تقييم العملاء</p>
                        </div>
                        <div class="w-px h-12 bg-slate-100"></div>
                        <div>
                            <p class="text-3xl font-display font-bold text-ink mb-1">24/7</p>
                            <p class="text-sm text-ink-muted flex items-center gap-1.5"><i data-lucide="clock" class="text-primary w-4 h-4"></i> دعم مستمر</p>
                        </div>
                    </div>
                </div>

                <!-- Right Content: The "Active Intake" Interactive Widget -->
                <!-- Completely redesigned with Lucide icons and soft UI -->
                <div class="lg:col-span-7 relative opacity-0 animate-fade-in-up" style="animation-delay: 0.2s;">
                    <div class="bg-white/80 backdrop-blur-3xl border border-white/60 p-10 rounded-[2.5rem] shadow-premium relative z-10">
                        
                        <div class="mb-10">
                            <h2 class="text-3xl font-display font-bold text-ink mb-3 tracking-tight">كيف يمكننا مساعدتك؟</h2>
                            <p class="text-base text-ink-muted">اختر ما يعبر عنك لنرشدك للرعاية الأنسب فوراً.</p>
                        </div>

                        <!-- Emotion/Condition Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-10">
                            
                            <!-- Card 1 -->
                            <button class="emotion-card bg-surface-subtle/50 border border-slate-100 p-6 rounded-[1.5rem] flex items-start gap-5 text-right cursor-pointer group outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                                <div class="icon-box w-12 h-12 shrink-0 rounded-2xl bg-white shadow-sm border border-slate-100 flex items-center justify-center text-primary group-hover:scale-105 group-hover:bg-primary/5 transition-all duration-500">
                                    <i data-lucide="brain" class="w-6 h-6"></i>
                                </div>
                                <div class="pt-0.5">
                                    <h3 class="font-display font-bold text-ink mb-1.5 group-hover:text-primary transition-colors text-lg">قلق وتوتر</h3>
                                    <p class="text-sm text-ink-muted leading-relaxed">تفكير مفرط، ضغط عمل، أو نوبات هلع.</p>
                                </div>
                            </button>

                            <!-- Card 2 -->
                            <button class="emotion-card bg-surface-subtle/50 border border-slate-100 p-6 rounded-[1.5rem] flex items-start gap-5 text-right cursor-pointer group outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                                <div class="icon-box w-12 h-12 shrink-0 rounded-2xl bg-white shadow-sm border border-slate-100 flex items-center justify-center text-primary group-hover:scale-105 group-hover:bg-primary/5 transition-all duration-500">
                                    <i data-lucide="cloud-rain" class="w-6 h-6"></i>
                                </div>
                                <div class="pt-0.5">
                                    <h3 class="font-display font-bold text-ink mb-1.5 group-hover:text-primary transition-colors text-lg">اكتئاب وحزن</h3>
                                    <p class="text-sm text-ink-muted leading-relaxed">فقدان الشغف، طاقة منخفضة، وعزلة.</p>
                                </div>
                            </button>

                            <!-- Card 3 -->
                            <button class="emotion-card bg-surface-subtle/50 border border-slate-100 p-6 rounded-[1.5rem] flex items-start gap-5 text-right cursor-pointer group outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                                <div class="icon-box w-12 h-12 shrink-0 rounded-2xl bg-white shadow-sm border border-slate-100 flex items-center justify-center text-primary group-hover:scale-105 group-hover:bg-primary/5 transition-all duration-500">
                                    <i data-lucide="users" class="w-6 h-6"></i>
                                </div>
                                <div class="pt-0.5">
                                    <h3 class="font-display font-bold text-ink mb-1.5 group-hover:text-primary transition-colors text-lg">علاقات أسرية</h3>
                                    <p class="text-sm text-ink-muted leading-relaxed">خلافات زوجية، تربية الأبناء، تواصل.</p>
                                </div>
                            </button>

                            <!-- Card 4 -->
                            <button class="emotion-card bg-surface-subtle/50 border border-slate-100 p-6 rounded-[1.5rem] flex items-start gap-5 text-right cursor-pointer group outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                                <div class="icon-box w-12 h-12 shrink-0 rounded-2xl bg-white shadow-sm border border-slate-100 flex items-center justify-center text-primary group-hover:scale-105 group-hover:bg-primary/5 transition-all duration-500">
                                    <i data-lucide="compass" class="w-6 h-6"></i>
                                </div>
                                <div class="pt-0.5">
                                    <h3 class="font-display font-bold text-ink mb-1.5 group-hover:text-primary transition-colors text-lg">تطوير الذات</h3>
                                    <p class="text-sm text-ink-muted leading-relaxed">بناء العادات، الثقة بالنفس، والأهداف.</p>
                                </div>
                            </button>

                        </div>

                        <!-- Dynamic Action Area -->
                        <div class="bg-primary-50/50 rounded-2xl p-6 flex flex-col sm:flex-row items-center justify-between gap-6 border border-primary-100/50">
                            <div class="flex items-center gap-5">
                                <div class="relative flex -space-x-3 rtl:space-x-reverse">
                                    <img class="w-11 h-11 rounded-full border-2 border-white object-cover shadow-sm" src="https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=100&h=100&fit=crop" alt="Doctor">
                                    <img class="w-11 h-11 rounded-full border-2 border-white object-cover shadow-sm" src="https://images.unsplash.com/photo-1594824436998-dd40e4f2081f?w=100&h=100&fit=crop" alt="Doctor">
                                    <div class="w-11 h-11 rounded-full border-2 border-white bg-primary text-white flex items-center justify-center text-xs font-bold shadow-sm">+12</div>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-ink mb-0.5">متاح 14 مختص الآن</p>
                                    <p class="text-xs text-primary-700 font-medium">وقت الانتظار: أقل من دقيقة</p>
                                </div>
                            </div>
                            <button class="w-full sm:w-auto bg-primary hover:bg-primary-600 text-white px-8 py-3.5 rounded-xl font-bold transition-all shadow-lg shadow-primary-500/20 hover:shadow-primary-500/40 hover:-translate-y-0.5">
                                ابدأ الجلسة
                            </button>
                        </div>
                    </div>
                    
                    <!-- Decorative Soft Elements behind the widget -->
                    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0" aria-hidden="true">
                        <div class="absolute -top-10 -right-10 w-32 h-32 bg-amber-100/60 rounded-full blur-3xl"></div>
                        <div class="absolute -bottom-14 -left-10 w-48 h-48 bg-primary-200/50 rounded-full blur-3xl"></div>
                    </div>
                </div>

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
                <button class="inline-flex items-center gap-2 text-primary font-bold hover:text-primary-700 transition-colors bg-primary-50 px-7 py-3.5 rounded-full">
                    عرض جميع المختصين <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="flex gap-8 overflow-x-auto overflow-y-hidden pb-16 pt-4 no-scrollbar snap-x snap-mandatory">
                
                <!-- Specialist Card 1 -->
                <div class="min-w-[340px] max-w-[340px] bg-surface rounded-[2rem] p-7 border border-slate-100 shadow-sm hover:shadow-card-hover hover:-translate-y-2 transition-all duration-500 snap-center relative group">
                    <!-- Online Badge -->
                    <div class="absolute top-7 left-7 flex items-center gap-2 bg-white/90 backdrop-blur-sm px-3.5 py-1.5 rounded-full shadow-sm border border-slate-50 z-10">
                        <span class="w-2 h-2 bg-primary rounded-full animate-pulse-slow"></span>
                        <span class="text-xs font-bold text-ink">متاح الآن</span>
                    </div>

                    <div class="relative mb-8 rounded-2xl overflow-hidden aspect-square bg-slate-50">
                        <img src="https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=500&h=500&fit=crop" alt="Dr. Sarah" class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-700 ease-out">
                    </div>
                    
                    <div class="mb-5">
                        <div class="flex justify-between items-start mb-3">
                            <h3 class="font-display font-bold text-xl text-ink">د. سارة الأحمد</h3>
                            <div class="flex items-center gap-1.5 text-sm font-bold text-ink bg-surface-subtle px-2.5 py-1 rounded-lg">
                                <i data-lucide="star" class="w-3.5 h-3.5 text-amber-400 fill-amber-400"></i> 4.9
                            </div>
                        </div>
                        <p class="text-sm text-primary font-semibold mb-2">استشاري طب نفسي</p>
                        <p class="text-sm text-ink-muted line-clamp-2 leading-relaxed">متخصصة في علاج الاكتئاب، اضطرابات القلق، والصدمات النفسية المعقدة.</p>
                    </div>

                    <div class="flex gap-2 mb-8">
                        <span class="text-xs bg-surface-subtle text-ink-subtle px-3.5 py-1.5 rounded-lg font-medium">علاج سلوكي</span>
                        <span class="text-xs bg-surface-subtle text-ink-subtle px-3.5 py-1.5 rounded-lg font-medium">نوبات هلع</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <button class="bg-surface-subtle hover:bg-slate-100 text-ink py-3.5 rounded-xl text-sm font-bold transition-colors">محادثة</button>
                        <button class="bg-primary hover:bg-primary-600 text-white py-3.5 rounded-xl text-sm font-bold transition-colors">مكالمة فيديو</button>
                    </div>
                </div>

                <!-- Specialist Card 2 -->
                <div class="min-w-[340px] max-w-[340px] bg-surface rounded-[2rem] p-7 border border-slate-100 shadow-sm hover:shadow-card-hover hover:-translate-y-2 transition-all duration-500 snap-center relative group">
                    <div class="relative mb-8 rounded-2xl overflow-hidden aspect-square bg-slate-50">
                        <img src="https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=500&h=500&fit=crop" alt="Dr. Fahad" class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-700 ease-out">
                        <div class="absolute bottom-5 right-5 bg-white/90 backdrop-blur-sm px-4 py-2 rounded-full text-xs font-bold text-ink shadow-sm">أقرب موعد: غداً</div>
                    </div>
                    
                    <div class="mb-5">
                        <div class="flex justify-between items-start mb-3">
                            <h3 class="font-display font-bold text-xl text-ink">أ. فهد العتيبي</h3>
                            <div class="flex items-center gap-1.5 text-sm font-bold text-ink bg-surface-subtle px-2.5 py-1 rounded-lg">
                                <i data-lucide="star" class="w-3.5 h-3.5 text-amber-400 fill-amber-400"></i> 4.8
                            </div>
                        </div>
                        <p class="text-sm text-primary font-semibold mb-2">أخصائي نفسي إكلينيكي</p>
                        <p class="text-sm text-ink-muted line-clamp-2 leading-relaxed">خبير في العلاج الزوجي، الإرشاد الأسري، والتعامل مع ضغوط بيئة العمل.</p>
                    </div>

                    <div class="flex gap-2 mb-8">
                        <span class="text-xs bg-surface-subtle text-ink-subtle px-3.5 py-1.5 rounded-lg font-medium">إرشاد أسري</span>
                        <span class="text-xs bg-surface-subtle text-ink-subtle px-3.5 py-1.5 rounded-lg font-medium">ضغوط العمل</span>
                    </div>

                    <button class="w-full bg-ink hover:bg-ink-subtle text-white py-3.5 rounded-xl text-sm font-bold transition-colors">جدولة موعد</button>
                </div>

                <!-- Specialist Card 3 -->
                <div class="min-w-[340px] max-w-[340px] bg-surface rounded-[2rem] p-7 border border-slate-100 shadow-sm hover:shadow-card-hover hover:-translate-y-2 transition-all duration-500 snap-center relative group">
                    <div class="absolute top-7 left-7 flex items-center gap-2 bg-white/90 backdrop-blur-sm px-3.5 py-1.5 rounded-full shadow-sm border border-slate-50 z-10">
                        <span class="w-2 h-2 bg-primary rounded-full animate-pulse-slow"></span>
                        <span class="text-xs font-bold text-ink">متاح الآن</span>
                    </div>
                    <div class="relative mb-8 rounded-2xl overflow-hidden aspect-square bg-slate-50">
                        <img src="https://images.unsplash.com/photo-1594824436998-dd40e4f2081f?w=500&h=500&fit=crop" alt="Dr. Reem" class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-700 ease-out">
                    </div>
                    
                    <div class="mb-5">
                        <div class="flex justify-between items-start mb-3">
                            <h3 class="font-display font-bold text-xl text-ink">أ. ريم الدوسري</h3>
                            <div class="flex items-center gap-1.5 text-sm font-bold text-ink bg-surface-subtle px-2.5 py-1 rounded-lg">
                                <i data-lucide="star" class="w-3.5 h-3.5 text-amber-400 fill-amber-400"></i> 5.0
                            </div>
                        </div>
                        <p class="text-sm text-primary font-semibold mb-2">أخصائية نفسية أطفال</p>
                        <p class="text-sm text-ink-muted line-clamp-2 leading-relaxed">متخصصة في تعديل السلوك، فرط الحركة، وتنمية مهارات الأطفال والمراهقين.</p>
                    </div>

                    <div class="flex gap-2 mb-8">
                        <span class="text-xs bg-surface-subtle text-ink-subtle px-3.5 py-1.5 rounded-lg font-medium">تعديل سلوك</span>
                        <span class="text-xs bg-surface-subtle text-ink-subtle px-3.5 py-1.5 rounded-lg font-medium">مراهقين</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <button class="bg-surface-subtle hover:bg-slate-100 text-ink py-3.5 rounded-xl text-sm font-bold transition-colors">محادثة</button>
                        <button class="bg-primary hover:bg-primary-600 text-white py-3.5 rounded-xl text-sm font-bold transition-colors">مكالمة فيديو</button>
                    </div>
                </div>

            </div>
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

    <!-- Interactive Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize Lucide Icons
            lucide.createIcons();

            // Emotion Cards Interaction
            const cards = document.querySelectorAll('.emotion-card');
            cards.forEach(card => {
                card.addEventListener('click', () => {
                    cards.forEach(c => c.classList.remove('active'));
                    card.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>