@auth
    <div
        id="patient-global-call-join-banner"
        class="pointer-events-none fixed inset-x-0 bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-[45] hidden px-3 sm:bottom-6 sm:px-4 sm:max-w-lg sm:left-auto sm:right-4"
        role="alert"
        aria-live="assertive"
    >
        <div class="pointer-events-auto rounded-2xl border border-emerald-300 bg-white p-4 shadow-lg shadow-emerald-900/15 ring-1 ring-emerald-100">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-emerald-950">{{ __('patient.appointments.incoming_call_title') }}</p>
                    <p id="patient-global-call-join-text" class="mt-0.5 text-sm text-emerald-800"></p>
                </div>
                <a
                    id="patient-global-call-join-btn"
                    href="#"
                    wire:navigate
                    class="inline-flex shrink-0 min-h-10 items-center justify-center rounded-xl bg-[#10B981] px-4 py-2 text-sm font-semibold text-white shadow-md shadow-emerald-900/20 transition hover:brightness-95"
                >
                    {{ __('patient.appointments.join_call') }}
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                function pendingCallKey(appointmentId) {
                    return 'mashora_pending_call_' + appointmentId;
                }

                function showGlobalJoinBanner(data) {
                    const banner = document.getElementById('patient-global-call-join-banner');
                    const text = document.getElementById('patient-global-call-join-text');
                    const btn = document.getElementById('patient-global-call-join-btn');
                    const template = @js(route('patient.appointments.conversation', ['appointment' => '__ID__']));

                    const appointmentId = Number(data?.appointment_id || 0);
                    if (!banner || !text || !btn || !appointmentId || !template) {
                        return;
                    }

                    const label = data.call_type === 'video'
                        ? @js(__('patient.appointments.incoming_video'))
                        : @js(__('patient.appointments.incoming_voice'));

                    text.textContent = label;
                    btn.href = template.replace('__ID__', String(appointmentId));
                    banner.classList.remove('hidden');
                }

                function hideGlobalJoinBanner(appointmentId) {
                    const banner = document.getElementById('patient-global-call-join-banner');
                    if (!banner) {
                        return;
                    }

                    const activeId = Number(banner.dataset.appointmentId || 0);

                    if (appointmentId && activeId && activeId !== appointmentId) {
                        return;
                    }

                    banner.classList.add('hidden');
                    delete banner.dataset.appointmentId;

                    if (appointmentId) {
                        try {
                            sessionStorage.removeItem(pendingCallKey(appointmentId));
                        } catch (_) {
                            // ignore storage errors
                        }
                    }
                }

                function restoreGlobalJoinBannerFromStorage() {
                    const template = @js(route('patient.appointments.conversation', ['appointment' => '__ID__']));
                    if (!template) {
                        return;
                    }

                    for (let i = 0; i < sessionStorage.length; i++) {
                        const key = sessionStorage.key(i) || '';
                        if (!key.startsWith('mashora_pending_call_')) {
                            continue;
                        }

                        try {
                            const raw = sessionStorage.getItem(key);
                            if (!raw) {
                                continue;
                            }

                            const data = JSON.parse(raw);
                            if (data?.agora_app_id && data?.appointment_id) {
                                showGlobalJoinBanner(data);
                                const banner = document.getElementById('patient-global-call-join-banner');
                                if (banner) {
                                    banner.dataset.appointmentId = String(data.appointment_id);
                                }

                                return;
                            }
                        } catch (_) {
                            // ignore parse errors
                        }
                    }
                }

                if (!window.__patientGlobalJoinBannerHook) {
                    window.__patientGlobalJoinBannerHook = true;

                    window.addEventListener('mashora:incoming-call', (event) => {
                        const data = event.detail || {};
                        showGlobalJoinBanner(data);
                        const banner = document.getElementById('patient-global-call-join-banner');
                        if (banner && data.appointment_id) {
                            banner.dataset.appointmentId = String(data.appointment_id);
                        }
                    });

                    window.addEventListener('mashora:call-ended', (event) => {
                        hideGlobalJoinBanner(Number(event.detail?.appointment_id || 0));
                    });

                    document.addEventListener('livewire:navigating', () => {
                        hideGlobalJoinBanner();
                    });
                }

                document.addEventListener('DOMContentLoaded', restoreGlobalJoinBannerFromStorage);
                document.addEventListener('livewire:navigated', restoreGlobalJoinBannerFromStorage);
                restoreGlobalJoinBannerFromStorage();
            })();
        </script>
    @endpush
@endauth
