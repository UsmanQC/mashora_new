    @push('scripts')
        <script>
            window.MashoraRealtimeAlerts = window.MashoraRealtimeAlerts || {};

            window.MashoraRealtimeAlerts.ringIntervalId = null;

            window.MashoraRealtimeAlerts.stopIncomingRing = function () {
                if (window.MashoraRealtimeAlerts.ringIntervalId) {
                    window.clearInterval(window.MashoraRealtimeAlerts.ringIntervalId);
                    window.MashoraRealtimeAlerts.ringIntervalId = null;
                }
            };

            /** Incoming calls are silent — stop any legacy ring loop from older cached scripts. */
            window.MashoraRealtimeAlerts.playIncomingRing = function () {
                window.MashoraRealtimeAlerts.stopIncomingRing();
            };

            window.MashoraRealtimeAlerts.showDesktopNotification = function (title, body) {
                if (!('Notification' in window)) {
                    return;
                }

                const show = () => {
                    try {
                        new Notification(title, { body, silent: true });
                    } catch (_) {
                        try {
                            new Notification(title, { body });
                        } catch (_) {
                            // Ignore notification errors.
                        }
                    }
                };

                if (Notification.permission === 'granted') {
                    show();
                } else if (Notification.permission === 'default') {
                    Notification.requestPermission().then((permission) => {
                        if (permission === 'granted') {
                            show();
                        }
                    });
                }
            };

            window.MashoraIncomingCall = window.MashoraIncomingCall || {};
            window.MashoraIncomingCall.dedupeMs = 5000;
            window.MashoraIncomingCall.lastKey = window.MashoraIncomingCall.lastKey || '';
            window.MashoraIncomingCall.lastAt = window.MashoraIncomingCall.lastAt || 0;

            window.MashoraIncomingCall.callKey = function (data) {
                const appointmentId = Number(data?.appointment_id || 0);
                const channel = data?.agora_channel || '';
                const callType = data?.call_type || '';

                return appointmentId + ':' + channel + ':' + callType;
            };

            window.MashoraIncomingCall.shouldNotify = function (data) {
                const key = this.callKey(data);
                const now = Date.now();

                if (key === this.lastKey && now - this.lastAt < this.dedupeMs) {
                    return false;
                }

                this.lastKey = key;
                this.lastAt = now;

                return true;
            };

            window.MashoraIncomingCall.notifyPatient = function (data, title, message, options = {}) {
                window.MashoraRealtimeAlerts?.stopIncomingRing();

                if (!this.shouldNotify(data)) {
                    return false;
                }

                if (options.showDesktopNotification === true) {
                    window.MashoraRealtimeAlerts?.showDesktopNotification(title, message);
                }

                if (options.showToast !== false && window.Flux?.toast) {
                    window.Flux.toast({ text: message, variant: 'success' });
                }

                return true;
            };

            window.MashoraPatientPusher = window.MashoraPatientPusher || {
                client: null,
                refCount: 0,

                acquire(options) {
                    const key = options?.key || '';
                    if (key === '') {
                        return null;
                    }

                    if (!this.client) {
                        this.client = new Pusher(key, {
                            cluster: options?.cluster || 'mt1',
                            authEndpoint: '/broadcasting/auth',
                            auth: {
                                headers: {
                                    'X-CSRF-TOKEN': options?.csrf || '',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            },
                        });

                        this.client.connection.bind('error', (error) => {
                            console.error('Pusher connection error', error);
                        });
                    }

                    this.refCount += 1;

                    return this.client;
                },

                release() {
                    if (this.refCount <= 0) {
                        return;
                    }

                    this.refCount -= 1;
                },

                subscribe(channelName) {
                    if (!this.client || !channelName) {
                        return null;
                    }

                    return this.client.subscribe(channelName);
                },

                unsubscribe(channelName) {
                    if (!this.client || !channelName) {
                        return;
                    }

                    this.client.unsubscribe(channelName);
                },
            };
        </script>
    @endpush
