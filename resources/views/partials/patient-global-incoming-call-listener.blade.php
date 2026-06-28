@auth
    @if (filled(config('broadcasting.connections.pusher.key')) && config('broadcasting.default') === 'pusher')
        <div
            id="patient-global-call-bootstrap"
            class="hidden"
            data-pusher-key="{{ config('broadcasting.connections.pusher.key') }}"
            data-pusher-cluster="{{ config('broadcasting.connections.pusher.options.cluster') }}"
            data-patient-id="{{ (int) auth()->id() }}"
            data-csrf="{{ csrf_token() }}"
            data-conversation-url="{{ route('patient.appointments.conversation', ['appointment' => '__ID__']) }}"
        ></div>

        @include('partials.realtime-call-alerts')

        @push('scripts')
            <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
            <script>
                function initPatientGlobalIncomingCallListener() {
                    const boot = document.getElementById('patient-global-call-bootstrap');
                    if (!boot || boot.dataset.initialized === '1') {
                        return;
                    }

                    const pusherKey = boot.dataset.pusherKey || '';
                    const patientId = Number(boot.dataset.patientId || 0);
                    if (!pusherKey || patientId <= 0) {
                        return;
                    }

                    boot.dataset.initialized = '1';

                    const pusherCluster = boot.dataset.pusherCluster || 'mt1';
                    const csrf = boot.dataset.csrf || '';
                    const conversationUrlTemplate = boot.dataset.conversationUrl || '';
                    const incomingCallTitle = @js(__('patient.appointments.incoming_call_title'));
                    const incomingVideoLabel = @js(__('patient.appointments.incoming_video'));
                    const incomingVoiceLabel = @js(__('patient.appointments.incoming_voice'));
                    const sessionStartedTitle = @js(__('patient.notifications.session_started_title'));
                    const sessionStartedBody = @js(__('patient.notifications.session_started_body'));

                    function pendingCallKey(appointmentId) {
                        return 'mashora_pending_call_' + appointmentId;
                    }

                    function storePendingCall(data) {
                        const appointmentId = Number(data?.appointment_id || 0);
                        if (!appointmentId || !data?.agora_app_id) {
                            return;
                        }

                        sessionStorage.setItem(pendingCallKey(appointmentId), JSON.stringify(data));
                    }

                    function handleSessionStarted(data) {
                        const appointmentId = Number(data?.appointment_id || 0);
                        if (!appointmentId) {
                            return;
                        }

                        window.MashoraRealtimeAlerts?.showDesktopNotification(sessionStartedTitle, sessionStartedBody);

                        if (window.Flux?.toast) {
                            window.Flux.toast({ text: sessionStartedBody, variant: 'success' });
                        }

                        window.dispatchEvent(new CustomEvent('mashora:session-started', { detail: data }));
                    }

                    function handleIncomingCall(data) {
                        const appointmentId = Number(data?.appointment_id || 0);
                        if (!appointmentId) {
                            return;
                        }

                        storePendingCall(data);

                        const label = data.call_type === 'video' ? incomingVideoLabel : incomingVoiceLabel;
                        window.MashoraIncomingCall?.notifyPatient(data, incomingCallTitle, label);

                        window.dispatchEvent(new CustomEvent('mashora:incoming-call', { detail: data }));
                    }

                    const pusher = new Pusher(pusherKey, {
                        cluster: pusherCluster,
                        authEndpoint: '/broadcasting/auth',
                        auth: {
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        },
                    });

                    pusher.connection.bind('error', (error) => {
                        console.error('Pusher connection error', error);
                    });

                    const patientChannel = pusher.subscribe('private-patient.' + patientId);
                    patientChannel.bind('pusher:subscription_error', (error) => {
                        console.error('Pusher patient channel error', error);
                    });
                    patientChannel.bind('session.join-requested', handleIncomingCall);
                    patientChannel.bind('appointment.session-started', handleSessionStarted);

                    if (!window.__patientGlobalIncomingNavigateHook) {
                        window.__patientGlobalIncomingNavigateHook = true;

                        document.addEventListener('livewire:navigating', () => {
                            window.MashoraRealtimeAlerts?.stopIncomingRing();
                        });
                    }
                }

                document.addEventListener('DOMContentLoaded', initPatientGlobalIncomingCallListener);
                document.addEventListener('livewire:navigated', initPatientGlobalIncomingCallListener);
                initPatientGlobalIncomingCallListener();
            </script>
        @endpush
    @endif
@endauth
