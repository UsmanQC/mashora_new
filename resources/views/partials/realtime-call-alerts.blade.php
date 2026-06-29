@once
    @push('scripts')
        <script>
            window.MashoraRealtimeAlerts = window.MashoraRealtimeAlerts || {
                ringIntervalId: null,
                audioContext: null,

                playIncomingRing() {
                    this.stopIncomingRing();

                    try {
                        const AudioContext = window.AudioContext || window.webkitAudioContext;
                        if (!AudioContext) {
                            return;
                        }

                        if (!this.audioContext) {
                            this.audioContext = new AudioContext();
                        }

                        const ctx = this.audioContext;
                        const playTone = () => {
                            const oscillator = ctx.createOscillator();
                            const gain = ctx.createGain();
                            oscillator.type = 'triangle';
                            oscillator.frequency.value = 880;
                            gain.gain.value = 0.08;
                            oscillator.connect(gain);
                            gain.connect(ctx.destination);
                            oscillator.start();
                            oscillator.stop(ctx.currentTime + 0.18);
                        };

                        playTone();
                        this.ringIntervalId = window.setInterval(playTone, 900);
                    } catch (_) {
                        // Ignore audio errors (permissions / unsupported browser).
                    }
                },

                stopIncomingRing() {
                    if (this.ringIntervalId) {
                        window.clearInterval(this.ringIntervalId);
                        this.ringIntervalId = null;
                    }
                },

                showDesktopNotification(title, body) {
                    if (!('Notification' in window)) {
                        return;
                    }

                    const show = () => {
                        try {
                            new Notification(title, { body });
                        } catch (_) {
                            // Ignore notification errors.
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
                },
            };

            window.MashoraIncomingCall = window.MashoraIncomingCall || {
                dedupeMs: 5000,
                lastKey: '',
                lastAt: 0,

                callKey(data) {
                    const appointmentId = Number(data?.appointment_id || 0);
                    const channel = data?.agora_channel || '';
                    const callType = data?.call_type || '';

                    return appointmentId + ':' + channel + ':' + callType;
                },

                shouldNotify(data) {
                    const key = this.callKey(data);
                    const now = Date.now();

                    if (key === this.lastKey && now - this.lastAt < this.dedupeMs) {
                        return false;
                    }

                    this.lastKey = key;
                    this.lastAt = now;

                    return true;
                },

                notifyPatient(data, title, message, options = {}) {
                    if (!this.shouldNotify(data)) {
                        return false;
                    }

                    if (options.playRing === true) {
                        window.MashoraRealtimeAlerts?.playIncomingRing();
                    }

                    if (options.showDesktopNotification !== false) {
                        window.MashoraRealtimeAlerts?.showDesktopNotification(title, message);
                    }

                    if (options.showToast !== false && window.Flux?.toast) {
                        window.Flux.toast({ text: message, variant: 'success' });
                    }

                    return true;
                },
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
@endonce
