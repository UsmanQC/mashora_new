@if (($forceVisible ?? false) || config('ai_chatbot.enabled'))
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @once
        <script src="https://unpkg.com/lucide@latest"></script>
    @endonce
    @php
        $chatbotQuickActionsAr = collect(__('ai_chatbot.quick_actions', [], 'ar'))
            ->map(static fn (string $label, string $intent): array => ['intent' => $intent, 'label' => $label])
            ->values();
        $chatbotQuickActionsEn = collect(__('ai_chatbot.quick_actions', [], 'en'))
            ->map(static fn (string $label, string $intent): array => ['intent' => $intent, 'label' => $label])
            ->values();
        $initialChatbotLocale = in_array(session('patient_locale'), ['ar', 'en'], true)
            ? session('patient_locale')
            : 'ar';
        $hideToggleOnMobile = (bool) ($hideToggle ?? false);
        $toggleAnchor = ($toggleAnchor ?? 'left') === 'right' ? 'right' : 'left';
        $layout = $layout ?? ($hideToggleOnMobile ? 'patient-dock' : 'corner');
        $isPatientDock = $layout === 'patient-dock';
        $abovePatientDock = (bool) ($abovePatientDock ?? false);
    @endphp

    <div
        id="awaan-ai-chatbot"
        @class([
            'font-sans',
            'patient-chatbot-dock-host pointer-events-none fixed inset-x-0 bottom-[calc(5.5rem+env(safe-area-inset-bottom))] z-[70] flex justify-center px-4' => $isPatientDock,
            'z-50' => ! $isPatientDock,
            'fixed bottom-[calc(5.5rem+env(safe-area-inset-bottom))] !left-6 !right-auto sm:bottom-6' => ! $isPatientDock && $toggleAnchor === 'left' && $abovePatientDock,
            'fixed bottom-6 !left-6 !right-auto' => ! $isPatientDock && $toggleAnchor === 'left' && ! $abovePatientDock,
            'fixed bottom-[calc(5.5rem+env(safe-area-inset-bottom))] !right-6 !left-auto sm:bottom-6' => ! $isPatientDock && $toggleAnchor === 'right' && $abovePatientDock,
            'fixed bottom-6 !right-6 !left-auto' => ! $isPatientDock && $toggleAnchor === 'right' && ! $abovePatientDock,
        ])
        dir="{{ $initialChatbotLocale === 'ar' ? 'rtl' : 'ltr' }}"
        data-initial-locale="{{ $initialChatbotLocale }}"
        data-hide-toggle-mobile="{{ $hideToggleOnMobile ? '1' : '0' }}"
        data-layout="{{ $layout }}"
    >
        <div
            id="awaan-ai-chatbot-panel"
            @class([
                'pointer-events-auto hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl w-[min(100vw-3rem,24rem)]',
                'mx-auto mb-0' => $isPatientDock,
                'mb-4' => ! $isPatientDock,
            ])
            role="dialog"
            aria-label="{{ __('ai_chatbot.title', [], $initialChatbotLocale) }}"
        >
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-primary px-5 py-4 text-white">
                <div class="min-w-0">
                    <p id="awaan-ai-chatbot-title" class="truncate font-display text-base font-bold">{{ __('ai_chatbot.title', [], $initialChatbotLocale) }}</p>
                    <p id="awaan-ai-chatbot-subtitle" class="truncate text-xs text-primary-50">{{ __('ai_chatbot.subtitle', [], $initialChatbotLocale) }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <button
                        type="button"
                        id="awaan-ai-chatbot-reset"
                        class="rounded-lg px-2 py-1 text-xs font-medium text-primary-50 transition hover:bg-white/10"
                        title="{{ __('ai_chatbot.reset', [], $initialChatbotLocale) }}"
                    >
                        <span id="awaan-ai-chatbot-reset-label">{{ __('ai_chatbot.reset', [], $initialChatbotLocale) }}</span>
                    </button>
                    <button
                        type="button"
                        id="awaan-ai-chatbot-close"
                        class="rounded-lg p-1.5 transition hover:bg-white/10"
                        aria-label="{{ __('ai_chatbot.close', [], $initialChatbotLocale) }}"
                    >
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
            </div>

            <div id="awaan-ai-chatbot-messages" class="flex max-h-80 flex-col gap-3 overflow-y-auto bg-surface-subtle p-4"></div>

            <form id="awaan-ai-chatbot-form" class="border-t border-slate-100 bg-white p-4">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <span id="awaan-ai-chatbot-language-label" class="text-xs font-medium text-ink-muted">{{ __('ai_chatbot.language', [], $initialChatbotLocale) }}</span>
                    <div
                        id="awaan-ai-chatbot-locale-switch"
                        class="inline-flex rounded-lg border border-slate-200 bg-surface-subtle p-0.5"
                        role="group"
                        aria-label="{{ __('ai_chatbot.language_aria', [], $initialChatbotLocale) }}"
                    >
                        <button
                            type="button"
                            data-chatbot-locale="ar"
                            class="rounded-md px-2.5 py-1 text-xs font-bold uppercase tracking-wide transition"
                        >
                            {{ __('ai_chatbot.locale_ar', [], $initialChatbotLocale) }}
                        </button>
                        <button
                            type="button"
                            data-chatbot-locale="en"
                            class="rounded-md px-2.5 py-1 text-xs font-bold uppercase tracking-wide transition"
                        >
                            {{ __('ai_chatbot.locale_en', [], $initialChatbotLocale) }}
                        </button>
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <textarea
                        id="awaan-ai-chatbot-input"
                        rows="2"
                        maxlength="{{ (int) config('ai_chatbot.max_message_length', 2000) }}"
                        placeholder="{{ __('ai_chatbot.placeholder', [], $initialChatbotLocale) }}"
                        class="min-h-[2.75rem] flex-1 resize-none rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-ink outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                    ></textarea>
                    <button
                        type="submit"
                        id="awaan-ai-chatbot-send"
                        class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary text-white transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-60"
                        aria-label="{{ __('ai_chatbot.send', [], $initialChatbotLocale) }}"
                    >
                        <i data-lucide="send" class="h-5 w-5"></i>
                    </button>
                </div>
            </form>
        </div>

        @if ($hideToggleOnMobile)
            <button
                type="button"
                id="awaan-ai-chatbot-toggle"
                class="pointer-events-auto hidden h-14 w-14 items-center justify-center rounded-full bg-primary text-white shadow-lg shadow-primary/30 transition hover:bg-primary-600 hover:shadow-xl sm:inline-flex"
                aria-label="{{ __('ai_chatbot.open', [], $initialChatbotLocale) }}"
            >
                <i data-lucide="message-circle" class="h-6 w-6"></i>
            </button>
        @else
            <button
                type="button"
                id="awaan-ai-chatbot-toggle"
                class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-primary text-white shadow-lg shadow-primary/30 transition hover:bg-primary-600 hover:shadow-xl"
                aria-label="{{ __('ai_chatbot.open', [], $initialChatbotLocale) }}"
            >
                <i data-lucide="message-circle" class="h-6 w-6"></i>
            </button>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.getElementById('awaan-ai-chatbot');
            if (!root) {
                return;
            }

            const panel = document.getElementById('awaan-ai-chatbot-panel');
            const toggle = document.getElementById('awaan-ai-chatbot-toggle');
            const closeBtn = document.getElementById('awaan-ai-chatbot-close');
            const resetBtn = document.getElementById('awaan-ai-chatbot-reset');
            const form = document.getElementById('awaan-ai-chatbot-form');
            const input = document.getElementById('awaan-ai-chatbot-input');
            const sendBtn = document.getElementById('awaan-ai-chatbot-send');
            const messagesEl = document.getElementById('awaan-ai-chatbot-messages');
            const localeSwitch = document.getElementById('awaan-ai-chatbot-locale-switch');
            const languageLabel = document.getElementById('awaan-ai-chatbot-language-label');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

            const copy = {
                ar: {
                    title: @json(__('ai_chatbot.title', [], 'ar')),
                    subtitle: @json(__('ai_chatbot.subtitle', [], 'ar')),
                    placeholder: @json(__('ai_chatbot.placeholder', [], 'ar')),
                    reset: @json(__('ai_chatbot.reset', [], 'ar')),
                    close: @json(__('ai_chatbot.close', [], 'ar')),
                    open: @json(__('ai_chatbot.open', [], 'ar')),
                    send: @json(__('ai_chatbot.send', [], 'ar')),
                    welcome: @json(__('ai_chatbot.welcome', [], 'ar')),
                    quickActions: @json($chatbotQuickActionsAr),
                    typing: @json(__('ai_chatbot.typing', [], 'ar')),
                    requestFailed: @json(__('ai_chatbot.request_failed', [], 'ar')),
                    networkError: @json(__('ai_chatbot.network_error', [], 'ar')),
                    language: @json(__('ai_chatbot.language', [], 'ar')),
                    languageAria: @json(__('ai_chatbot.language_aria', [], 'ar')),
                    booking: {
                        continue: @json(__('ai_chatbot.booking.continue', [], 'ar')),
                        skipStep: @json(__('ai_chatbot.booking.skip_step', [], 'ar')),
                        continueBooking: @json(__('ai_chatbot.booking.continue_booking', [], 'ar')),
                        viewAllSpecialists: @json(__('ai_chatbot.booking.view_all_specialists', [], 'ar')),
                        onlineNow: @json(__('ai_chatbot.booking.online_now', [], 'ar')),
                        noDoctors: @json(__('ai_chatbot.booking.no_doctors', [], 'ar')),
                        nearestSlot: @json(__('ai_chatbot.booking.nearest_slot', [], 'ar')),
                    },
                },
                en: {
                    title: @json(__('ai_chatbot.title', [], 'en')),
                    subtitle: @json(__('ai_chatbot.subtitle', [], 'en')),
                    placeholder: @json(__('ai_chatbot.placeholder', [], 'en')),
                    reset: @json(__('ai_chatbot.reset', [], 'en')),
                    close: @json(__('ai_chatbot.close', [], 'en')),
                    open: @json(__('ai_chatbot.open', [], 'en')),
                    send: @json(__('ai_chatbot.send', [], 'en')),
                    welcome: @json(__('ai_chatbot.welcome', [], 'en')),
                    quickActions: @json($chatbotQuickActionsEn),
                    typing: @json(__('ai_chatbot.typing', [], 'en')),
                    requestFailed: @json(__('ai_chatbot.request_failed', [], 'en')),
                    networkError: @json(__('ai_chatbot.network_error', [], 'en')),
                    language: @json(__('ai_chatbot.language', [], 'en')),
                    languageAria: @json(__('ai_chatbot.language_aria', [], 'en')),
                    booking: {
                        continue: @json(__('ai_chatbot.booking.continue', [], 'en')),
                        skipStep: @json(__('ai_chatbot.booking.skip_step', [], 'en')),
                        continueBooking: @json(__('ai_chatbot.booking.continue_booking', [], 'en')),
                        viewAllSpecialists: @json(__('ai_chatbot.booking.view_all_specialists', [], 'en')),
                        onlineNow: @json(__('ai_chatbot.booking.online_now', [], 'en')),
                        noDoctors: @json(__('ai_chatbot.booking.no_doctors', [], 'en')),
                        nearestSlot: @json(__('ai_chatbot.booking.nearest_slot', [], 'en')),
                    },
                },
            };

            const routes = {
                message: @json(route('api.chat')),
                reset: @json(route('api.chat.reset')),
                bookingStep: @json(route('api.chat.booking.step')),
                bookingComplete: @json(route('api.chat.booking.complete')),
            };

            const localeStorageKey = 'awaan-ai-chatbot-locale';
            let locale = localStorage.getItem(localeStorageKey) ?? root.dataset.initialLocale ?? 'ar';

            if (!['ar', 'en'].includes(locale)) {
                locale = 'ar';
            }

            let loading = false;
            let quickActionsVisible = false;
            let bookingFlowActive = false;
            let bookingPreferences = {};
            let bookingMultiSelected = [];
            let bookingCurrentStep = null;

            const labels = () => copy[locale];
            const bookingLabels = () => labels().booking;

            const isCoarsePointer = () => window.matchMedia('(pointer: coarse)').matches;

            const hasInteractiveChoices = () => {
                return quickActionsVisible || document.getElementById('awaan-ai-chatbot-selection') !== null;
            };

            const focusInputIfNeeded = () => {
                if (isCoarsePointer() || hasInteractiveChoices()) {
                    return;
                }

                input.focus();
            };

            const blurInput = () => {
                if (document.activeElement === input) {
                    input.blur();
                }
            };

            const bubbleClasses = (role) => {
                if (locale === 'ar') {
                    return role === 'user'
                        ? 'ml-8 rounded-2xl rounded-bl-md bg-primary px-4 py-3 text-sm text-white'
                        : 'mr-8 rounded-2xl rounded-br-md border border-slate-200 bg-white px-4 py-3 text-sm text-ink';
                }

                return role === 'user'
                    ? 'mr-8 rounded-2xl rounded-br-md bg-primary px-4 py-3 text-sm text-white'
                    : 'ml-8 rounded-2xl rounded-bl-md border border-slate-200 bg-white px-4 py-3 text-sm text-ink';
            };

            const typingClasses = () => locale === 'ar'
                ? 'mr-8 rounded-2xl rounded-br-md border border-slate-200 bg-white px-4 py-3 text-sm text-ink-muted'
                : 'ml-8 rounded-2xl rounded-bl-md border border-slate-200 bg-white px-4 py-3 text-sm text-ink-muted';

            const quickActionsClasses = () => locale === 'ar'
                ? 'mr-8 flex flex-col gap-2'
                : 'ml-8 flex flex-col gap-2';

            const chipClasses = () => 'rounded-xl border border-primary/20 bg-white px-3 py-2.5 text-start text-sm font-medium text-primary transition hover:border-primary hover:bg-primary/5 disabled:cursor-not-allowed disabled:opacity-60';

            const chipActiveClasses = () => 'rounded-xl border border-primary bg-primary/10 px-3 py-2.5 text-start text-sm font-semibold text-primary transition disabled:cursor-not-allowed disabled:opacity-60';

            const selectionPanelClasses = () => locale === 'ar'
                ? 'mr-8 flex flex-col gap-2'
                : 'ml-8 flex flex-col gap-2';

            const doctorCardClasses = () => 'flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 text-start transition hover:border-primary hover:bg-primary/5 disabled:cursor-not-allowed disabled:opacity-60';

            const linkButtonClasses = () => 'inline-flex items-center justify-center rounded-xl bg-primary px-4 py-3 text-sm font-bold text-white transition hover:bg-primary-600';

            const applyLocale = (nextLocale, { resetWelcome = false } = {}) => {
                locale = nextLocale;
                localStorage.setItem(localeStorageKey, locale);

                root.dir = locale === 'ar' ? 'rtl' : 'ltr';
                panel.setAttribute('aria-label', labels().title);

                document.getElementById('awaan-ai-chatbot-title').textContent = labels().title;
                document.getElementById('awaan-ai-chatbot-subtitle').textContent = labels().subtitle;
                document.getElementById('awaan-ai-chatbot-reset-label').textContent = labels().reset;
                resetBtn.title = labels().reset;
                closeBtn.setAttribute('aria-label', labels().close);
                toggle?.setAttribute('aria-label', labels().open);
                sendBtn.setAttribute('aria-label', labels().send);
                input.placeholder = labels().placeholder;
                languageLabel.textContent = labels().language;
                localeSwitch.setAttribute('aria-label', labels().languageAria);

                localeSwitch.querySelectorAll('[data-chatbot-locale]').forEach((button) => {
                    const isActive = button.dataset.chatbotLocale === locale;
                    button.classList.toggle('bg-primary', isActive);
                    button.classList.toggle('text-white', isActive);
                    button.classList.toggle('text-ink-muted', !isActive);
                    button.classList.toggle('hover:bg-slate-100', !isActive);
                });

                messagesEl.querySelectorAll('[data-chatbot-role]').forEach((bubble) => {
                    bubble.className = bubbleClasses(bubble.dataset.chatbotRole);
                });

                const typing = document.getElementById('awaan-ai-chatbot-typing');
                if (typing) {
                    typing.className = typingClasses();
                    typing.textContent = labels().typing;
                }

                if (resetWelcome && (quickActionsVisible || bookingFlowActive)) {
                    exitBookingFlow();
                    showWelcomeState({ replace: true });
                }
            };

            const exitBookingFlow = () => {
                bookingFlowActive = false;
                bookingPreferences = {};
                bookingMultiSelected = [];
                bookingCurrentStep = null;
                removeSelectionPanel();
            };

            const removeSelectionPanel = () => {
                document.getElementById('awaan-ai-chatbot-selection')?.remove();
            };

            const preferenceKeyForStep = (step) => ({
                degree: 'degree_id',
                duration: 'duration_minutes',
                gender: 'gender_preference',
                language: 'language_preference',
            })[step] ?? null;

            const fetchBookingStep = async (step, preferences = bookingPreferences) => {
                const response = await fetch(routes.bookingStep, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ step, preferences, locale }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message ?? labels().requestFailed);
                }

                return data;
            };

            const renderSelectionPanel = (stepData) => {
                removeSelectionPanel();
                bookingCurrentStep = stepData.step;

                const container = document.createElement('div');
                container.id = 'awaan-ai-chatbot-selection';
                container.className = selectionPanelClasses();
                container.setAttribute('role', 'group');

                if (stepData.mode === 'single') {
                    stepData.options.forEach((option) => {
                        const chip = document.createElement('button');
                        chip.type = 'button';
                        chip.className = chipClasses();
                        chip.textContent = option.label;
                        chip.addEventListener('click', () => selectSingleOption(stepData, option));
                        container.appendChild(chip);
                    });
                }

                if (stepData.mode === 'multi') {
                    stepData.options.forEach((option) => {
                        const chip = document.createElement('button');
                        chip.type = 'button';
                        chip.dataset.optionId = option.id;
                        chip.className = bookingMultiSelected.includes(option.id) ? chipActiveClasses() : chipClasses();
                        chip.textContent = option.label;
                        chip.addEventListener('click', () => toggleMultiOption(option.id, chip));
                        container.appendChild(chip);
                    });

                    const actions = document.createElement('div');
                    actions.className = 'mt-1 flex flex-wrap gap-2';

                    if (stepData.allow_skip) {
                        const skipBtn = document.createElement('button');
                        skipBtn.type = 'button';
                        skipBtn.className = chipClasses();
                        skipBtn.textContent = stepData.skip_label ?? bookingLabels().skipStep;
                        skipBtn.addEventListener('click', () => advanceBooking(stepData.next_step, bookingPreferences));
                        actions.appendChild(skipBtn);
                    }

                    const continueBtn = document.createElement('button');
                    continueBtn.type = 'button';
                    continueBtn.className = linkButtonClasses();
                    continueBtn.textContent = stepData.continue_label ?? bookingLabels().continue;
                    continueBtn.addEventListener('click', () => {
                        bookingPreferences.subspecialties = [...bookingMultiSelected];
                        advanceBooking(stepData.next_step, bookingPreferences);
                    });
                    actions.appendChild(continueBtn);
                    container.appendChild(actions);
                }

                if (stepData.mode === 'doctors') {
                    if ((stepData.doctors ?? []).length === 0) {
                        const empty = document.createElement('p');
                        empty.className = 'text-sm text-ink-muted';
                        empty.textContent = bookingLabels().noDoctors;
                        container.appendChild(empty);
                    }

                    stepData.doctors.forEach((doctor) => {
                        const card = document.createElement('button');
                        card.type = 'button';
                        card.className = doctorCardClasses();

                        const photo = document.createElement('img');
                        photo.src = doctor.photo_url || '';
                        photo.alt = '';
                        photo.className = 'size-11 shrink-0 rounded-full border border-slate-100 bg-slate-50 object-cover';
                        photo.onerror = () => { photo.style.display = 'none'; };
                        card.appendChild(photo);

                        const meta = document.createElement('div');
                        meta.className = 'min-w-0 flex-1';

                        const name = document.createElement('p');
                        name.className = 'truncate text-sm font-bold text-ink';
                        name.textContent = doctor.label;
                        meta.appendChild(name);

                        if (doctor.degree_title) {
                            const degree = document.createElement('p');
                            degree.className = 'truncate text-xs text-ink-muted';
                            degree.textContent = doctor.degree_title;
                            meta.appendChild(degree);
                        }

                        if (Array.isArray(doctor.tags) && doctor.tags.length > 0) {
                            const tags = document.createElement('p');
                            tags.className = 'mt-0.5 truncate text-xs text-primary';
                            tags.textContent = doctor.tags.join(' · ');
                            meta.appendChild(tags);
                        }

                        if (doctor.is_online) {
                            const online = document.createElement('p');
                            online.className = 'mt-0.5 text-xs font-semibold text-emerald-600';
                            online.textContent = bookingLabels().onlineNow;
                            meta.appendChild(online);
                        }

                        card.appendChild(meta);
                        card.addEventListener('click', () => selectDoctor(stepData, doctor));
                        container.appendChild(card);
                    });

                    if (stepData.specialists_url) {
                        const viewAll = document.createElement('a');
                        viewAll.href = stepData.specialists_url;
                        viewAll.className = chipClasses() + ' text-center';
                        viewAll.textContent = bookingLabels().viewAllSpecialists;
                        container.appendChild(viewAll);
                    }
                }

                if (stepData.mode === 'link') {
                    if (stepData.nearest_slot?.date && stepData.nearest_slot?.time) {
                        const slotText = bookingLabels().nearestSlot
                            .replace(':date', stepData.nearest_slot.date)
                            .replace(':time', stepData.nearest_slot.time);
                        const slot = document.createElement('p');
                        slot.className = 'text-xs text-ink-muted';
                        slot.textContent = slotText;
                        container.appendChild(slot);
                    }

                    if (stepData.booking_url) {
                        const link = document.createElement('a');
                        link.href = stepData.booking_url;
                        link.className = linkButtonClasses();
                        link.textContent = stepData.link_label ?? bookingLabels().continueBooking;
                        container.appendChild(link);
                    }
                }

                messagesEl.appendChild(container);
                scrollMessages();
                blurInput();
            };

            const selectSingleOption = async (stepData, option) => {
                appendBubble('user', option.label);
                removeSelectionPanel();

                const key = preferenceKeyForStep(stepData.step);
                if (key) {
                    bookingPreferences[key] = option.id;
                }

                await advanceBooking(stepData.next_step, bookingPreferences);
            };

            const toggleMultiOption = (optionId, chip) => {
                if (bookingMultiSelected.includes(optionId)) {
                    bookingMultiSelected = bookingMultiSelected.filter((id) => id !== optionId);
                    chip.className = chipClasses();
                } else {
                    bookingMultiSelected = [...bookingMultiSelected, optionId];
                    chip.className = chipActiveClasses();
                }
            };

            const selectDoctor = async (stepData, doctor) => {
                appendBubble('user', doctor.label);
                removeSelectionPanel();
                bookingPreferences.doctor_id = parseInt(doctor.id, 10);
                await advanceBooking(stepData.next_step, bookingPreferences);
            };

            const advanceBooking = async (nextStep, preferences) => {
                if (!nextStep || loading) {
                    return;
                }

                setLoading(true);

                try {
                    const data = await fetchBookingStep(nextStep, preferences);
                    bookingPreferences = data.preferences ?? preferences;
                    appendBubble('assistant', data.prompt ?? '');

                    if (data.mode === 'link') {
                        bookingFlowActive = false;
                        renderSelectionPanel(data);
                    } else {
                        if (data.step === 'speciality') {
                            bookingMultiSelected = [...(bookingPreferences.subspecialties ?? [])];
                        }
                        renderSelectionPanel(data);
                    }
                } catch (error) {
                    appendBubble('assistant', error.message ?? labels().networkError);
                    bookingFlowActive = false;
                } finally {
                    setLoading(false);
                    focusInputIfNeeded();
                }
            };

            const startBookingFlow = async (startStep = 'degree') => {
                if (loading) {
                    return;
                }

                bookingFlowActive = true;
                bookingPreferences = {};
                bookingMultiSelected = [];
                removeQuickActions();
                setLoading(true);

                try {
                    const data = await fetchBookingStep(startStep, bookingPreferences);
                    bookingPreferences = data.preferences ?? {};
                    appendBubble('assistant', data.prompt ?? '');
                    if (data.step === 'speciality') {
                        bookingMultiSelected = [...(bookingPreferences.subspecialties ?? [])];
                    }
                    renderSelectionPanel(data);
                } catch (error) {
                    appendBubble('assistant', error.message ?? labels().networkError);
                    bookingFlowActive = false;
                } finally {
                    setLoading(false);
                }
            };

            const scrollMessages = () => {
                messagesEl.scrollTop = messagesEl.scrollHeight;
            };

            const appendBubble = (role, text) => {
                const bubble = document.createElement('div');
                bubble.dataset.chatbotRole = role;
                bubble.className = bubbleClasses(role);
                bubble.textContent = text;
                messagesEl.appendChild(bubble);
                scrollMessages();
            };

            const removeQuickActions = () => {
                document.getElementById('awaan-ai-chatbot-quick-actions')?.remove();
                quickActionsVisible = false;
            };

            const renderQuickActions = () => {
                removeQuickActions();

                const container = document.createElement('div');
                container.id = 'awaan-ai-chatbot-quick-actions';
                container.className = quickActionsClasses();
                container.setAttribute('role', 'group');
                container.setAttribute('aria-label', labels().subtitle);

                labels().quickActions.forEach((action) => {
                    const chip = document.createElement('button');
                    chip.type = 'button';
                    chip.className = chipClasses();
                    chip.textContent = action.label;
                    chip.addEventListener('click', () => handleQuickAction(action));
                    container.appendChild(chip);
                });

                messagesEl.appendChild(container);
                quickActionsVisible = true;
                scrollMessages();
                blurInput();
            };

            const showWelcomeState = ({ replace = false } = {}) => {
                if (replace) {
                    messagesEl.innerHTML = '';
                }

                appendBubble('assistant', labels().welcome);
                renderQuickActions();
            };

            const setLoading = (isLoading) => {
                loading = isLoading;
                sendBtn.disabled = isLoading;
                input.disabled = isLoading;

                document.querySelectorAll('#awaan-ai-chatbot-quick-actions button, #awaan-ai-chatbot-selection button, #awaan-ai-chatbot-selection a').forEach((element) => {
                    if (element.tagName === 'A' && !isLoading) {
                        return;
                    }
                    if (element.tagName === 'BUTTON') {
                        element.disabled = isLoading;
                    }
                });
            };

            const showTyping = () => {
                const typing = document.createElement('div');
                typing.id = 'awaan-ai-chatbot-typing';
                typing.className = typingClasses();
                typing.textContent = labels().typing;
                messagesEl.appendChild(typing);
                scrollMessages();
            };

            const hideTyping = () => {
                document.getElementById('awaan-ai-chatbot-typing')?.remove();
            };

            const sendUserMessage = async (message, { skipUserBubble = false } = {}) => {
                if (message === '' || loading) {
                    return;
                }

                if (!skipUserBubble) {
                    appendBubble('user', message);
                }

                exitBookingFlow();
                setLoading(true);
                showTyping();

                try {
                    const response = await fetch(routes.message, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ message, locale }),
                    });

                    hideTyping();

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        appendBubble('assistant', data.message ?? labels().requestFailed);
                        return;
                    }

                    appendBubble('assistant', data.reply ?? labels().requestFailed);
                } catch (error) {
                    hideTyping();
                    appendBubble('assistant', labels().networkError);
                } finally {
                    setLoading(false);
                    focusInputIfNeeded();
                }
            };

            const handleQuickAction = async (action) => {
                if (loading) {
                    return;
                }

                removeQuickActions();
                appendBubble('user', action.label);

                if (action.intent === 'book') {
                    await startBookingFlow('degree');
                    return;
                }

                if (action.intent === 'specialty') {
                    await startBookingFlow('speciality');
                    return;
                }

                await sendUserMessage(action.label, { skipUserBubble: true });
            };

            const openPanel = () => {
                panel.classList.remove('hidden');
                if (messagesEl.childElementCount === 0) {
                    showWelcomeState();
                }

                blurInput();
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            };

            applyLocale(locale);

            localeSwitch.querySelectorAll('[data-chatbot-locale]').forEach((button) => {
                button.addEventListener('click', () => {
                    const nextLocale = button.dataset.chatbotLocale;
                    if (nextLocale === locale) {
                        return;
                    }

                    applyLocale(nextLocale, { resetWelcome: true });
                });
            });

            window.openAwaanAiChatbot = openPanel;

            document.addEventListener('click', (event) => {
                const trigger = event.target.closest('[data-open-ai-chatbot]');
                if (!trigger) {
                    return;
                }

                event.preventDefault();
                openPanel();
            });

            toggle?.addEventListener('click', () => {
                if (panel.classList.contains('hidden')) {
                    openPanel();
                } else {
                    panel.classList.add('hidden');
                }
            });

            closeBtn.addEventListener('click', () => panel.classList.add('hidden'));

            resetBtn.addEventListener('click', async () => {
                exitBookingFlow();
                showWelcomeState({ replace: true });

                try {
                    await fetch(routes.reset, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                    });
                } catch (error) {
                    // Ignore reset network errors; UI is already cleared locally.
                }
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const message = input.value.trim();
                if (message === '' || loading) {
                    return;
                }

                removeQuickActions();
                input.value = '';
                await sendUserMessage(message);
            });
        });
    </script>
@endif
