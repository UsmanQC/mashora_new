@if (($forceVisible ?? false) || config('ai_chatbot.enabled'))
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @once
        <script src="https://unpkg.com/lucide@latest"></script>
    @endonce

    @php
        $initialChatbotLocale = in_array(session('patient_locale'), ['ar', 'en'], true)
            ? session('patient_locale')
            : 'ar';
    @endphp

    <div
        id="awaan-ai-chatbot"
        class="fixed bottom-6 left-6 z-[60] font-sans"
        dir="{{ $initialChatbotLocale === 'ar' ? 'rtl' : 'ltr' }}"
        data-initial-locale="{{ $initialChatbotLocale }}"
    >
        <div
            id="awaan-ai-chatbot-panel"
            class="hidden mb-4 w-[min(100vw-3rem,24rem)] overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl"
            role="dialog"
            aria-label="{{ __('ai_chatbot.title', [], $initialChatbotLocale) }}"
        >
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-primary px-5 py-4 text-white">
                <div class="min-w-0">
                    <p id="awaan-ai-chatbot-title" class="truncate font-display text-base font-bold">{{ __('ai_chatbot.title', [], $initialChatbotLocale) }}</p>
                    <p id="awaan-ai-chatbot-subtitle" class="truncate text-xs text-primary-50">{{ __('ai_chatbot.subtitle', [], $initialChatbotLocale) }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <div
                        id="awaan-ai-chatbot-locale-switch"
                        class="inline-flex rounded-lg border border-white/20 bg-white/10 p-0.5"
                        role="group"
                        aria-label="{{ __('ai_chatbot.language_aria', [], $initialChatbotLocale) }}"
                    >
                        <button
                            type="button"
                            data-chatbot-locale="ar"
                            class="rounded-md px-2 py-1 text-xs font-bold uppercase tracking-wide transition"
                        >
                            {{ __('ai_chatbot.locale_ar', [], $initialChatbotLocale) }}
                        </button>
                        <button
                            type="button"
                            data-chatbot-locale="en"
                            class="rounded-md px-2 py-1 text-xs font-bold uppercase tracking-wide transition"
                        >
                            {{ __('ai_chatbot.locale_en', [], $initialChatbotLocale) }}
                        </button>
                    </div>
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

        <button
            type="button"
            id="awaan-ai-chatbot-toggle"
            class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-primary text-white shadow-lg shadow-primary/30 transition hover:bg-primary-600 hover:shadow-xl"
            aria-label="{{ __('ai_chatbot.open', [], $initialChatbotLocale) }}"
        >
            <i data-lucide="message-circle" class="h-6 w-6"></i>
        </button>
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
                    typing: @json(__('ai_chatbot.typing', [], 'ar')),
                    requestFailed: @json(__('ai_chatbot.request_failed', [], 'ar')),
                    networkError: @json(__('ai_chatbot.network_error', [], 'ar')),
                    languageAria: @json(__('ai_chatbot.language_aria', [], 'ar')),
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
                    typing: @json(__('ai_chatbot.typing', [], 'en')),
                    requestFailed: @json(__('ai_chatbot.request_failed', [], 'en')),
                    networkError: @json(__('ai_chatbot.network_error', [], 'en')),
                    languageAria: @json(__('ai_chatbot.language_aria', [], 'en')),
                },
            };

            const routes = {
                message: @json(route('api.chat')),
                reset: @json(route('api.chat.reset')),
            };

            const localeStorageKey = 'awaan-ai-chatbot-locale';
            let locale = localStorage.getItem(localeStorageKey) ?? root.dataset.initialLocale ?? 'ar';

            if (!['ar', 'en'].includes(locale)) {
                locale = 'ar';
            }

            let loading = false;

            const labels = () => copy[locale];

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
                toggle.setAttribute('aria-label', labels().open);
                sendBtn.setAttribute('aria-label', labels().send);
                input.placeholder = labels().placeholder;
                localeSwitch.setAttribute('aria-label', labels().languageAria);

                localeSwitch.querySelectorAll('[data-chatbot-locale]').forEach((button) => {
                    const isActive = button.dataset.chatbotLocale === locale;
                    button.classList.toggle('bg-white', isActive);
                    button.classList.toggle('text-primary', isActive);
                    button.classList.toggle('text-primary-50', !isActive);
                    button.classList.toggle('hover:bg-white/10', !isActive);
                });

                messagesEl.querySelectorAll('[data-chatbot-role]').forEach((bubble) => {
                    bubble.className = bubbleClasses(bubble.dataset.chatbotRole);
                });

                const typing = document.getElementById('awaan-ai-chatbot-typing');
                if (typing) {
                    typing.className = typingClasses();
                    typing.textContent = labels().typing;
                }

                if (resetWelcome && messagesEl.childElementCount === 1) {
                    const firstBubble = messagesEl.querySelector('[data-chatbot-role="assistant"]');
                    if (firstBubble) {
                        firstBubble.textContent = labels().welcome;
                    }
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

            const setLoading = (isLoading) => {
                loading = isLoading;
                sendBtn.disabled = isLoading;
                input.disabled = isLoading;
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

            const openPanel = () => {
                panel.classList.remove('hidden');
                if (messagesEl.childElementCount === 0) {
                    appendBubble('assistant', labels().welcome);
                }
                input.focus();
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

            document.querySelectorAll('[data-open-ai-chatbot]').forEach((trigger) => {
                trigger.addEventListener('click', (event) => {
                    event.preventDefault();
                    openPanel();
                });
            });

            toggle.addEventListener('click', () => {
                if (panel.classList.contains('hidden')) {
                    openPanel();
                } else {
                    panel.classList.add('hidden');
                }
            });

            closeBtn.addEventListener('click', () => panel.classList.add('hidden'));

            resetBtn.addEventListener('click', async () => {
                messagesEl.innerHTML = '';
                appendBubble('assistant', labels().welcome);

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

                appendBubble('user', message);
                input.value = '';
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
                    input.focus();
                }
            });
        });
    </script>
@endif
