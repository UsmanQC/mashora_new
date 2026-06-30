@if (($forceVisible ?? false) || config('ai_chatbot.enabled'))
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @once
        <script src="https://unpkg.com/lucide@latest"></script>
    @endonce

    @php
        app()->setLocale('ar');
    @endphp

    <div id="awaan-ai-chatbot" class="fixed bottom-6 left-6 z-[60] font-sans" dir="rtl">
        <div
            id="awaan-ai-chatbot-panel"
            class="hidden mb-4 w-[min(100vw-3rem,24rem)] overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl"
            role="dialog"
            aria-label="{{ __('ai_chatbot.title') }}"
        >
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-primary px-5 py-4 text-white">
                <div class="min-w-0">
                    <p class="truncate font-display text-base font-bold">{{ __('ai_chatbot.title') }}</p>
                    <p class="truncate text-xs text-primary-50">{{ __('ai_chatbot.subtitle') }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <button
                        type="button"
                        id="awaan-ai-chatbot-reset"
                        class="rounded-lg px-2 py-1 text-xs font-medium text-primary-50 transition hover:bg-white/10"
                        title="{{ __('ai_chatbot.reset') }}"
                    >
                        {{ __('ai_chatbot.reset') }}
                    </button>
                    <button
                        type="button"
                        id="awaan-ai-chatbot-close"
                        class="rounded-lg p-1.5 transition hover:bg-white/10"
                        aria-label="{{ __('ai_chatbot.close') }}"
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
                        placeholder="{{ __('ai_chatbot.placeholder') }}"
                        class="min-h-[2.75rem] flex-1 resize-none rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-ink outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                    ></textarea>
                    <button
                        type="submit"
                        id="awaan-ai-chatbot-send"
                        class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary text-white transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-60"
                        aria-label="{{ __('ai_chatbot.send') }}"
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
            aria-label="{{ __('ai_chatbot.open') }}"
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
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

            const labels = {
                welcome: @json(__('ai_chatbot.welcome')),
                typing: @json(__('ai_chatbot.typing')),
                requestFailed: @json(__('ai_chatbot.request_failed')),
                networkError: @json(__('ai_chatbot.network_error')),
            };

            const routes = {
                message: @json(route('api.chat')),
                reset: @json(route('api.chat.reset')),
            };

            let loading = false;

            const scrollMessages = () => {
                messagesEl.scrollTop = messagesEl.scrollHeight;
            };

            const appendBubble = (role, text) => {
                const bubble = document.createElement('div');
                bubble.className = role === 'user'
                    ? 'ml-8 rounded-2xl rounded-bl-md bg-primary px-4 py-3 text-sm text-white'
                    : 'mr-8 rounded-2xl rounded-br-md border border-slate-200 bg-white px-4 py-3 text-sm text-ink';

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
                typing.className = 'mr-8 rounded-2xl rounded-br-md border border-slate-200 bg-white px-4 py-3 text-sm text-ink-muted';
                typing.textContent = labels.typing;
                messagesEl.appendChild(typing);
                scrollMessages();
            };

            const hideTyping = () => {
                document.getElementById('awaan-ai-chatbot-typing')?.remove();
            };

            const openPanel = () => {
                panel.classList.remove('hidden');
                if (messagesEl.childElementCount === 0) {
                    appendBubble('assistant', labels.welcome);
                }
                input.focus();
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            };

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
                appendBubble('assistant', labels.welcome);

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
                        body: JSON.stringify({ message }),
                    });

                    hideTyping();

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        appendBubble('assistant', data.message ?? labels.requestFailed);
                        return;
                    }

                    appendBubble('assistant', data.reply ?? labels.requestFailed);
                } catch (error) {
                    hideTyping();
                    appendBubble('assistant', labels.networkError);
                } finally {
                    setLoading(false);
                    input.focus();
                }
            });
        });
    </script>
@endif
