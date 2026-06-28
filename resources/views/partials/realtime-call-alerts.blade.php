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

                    if (options.playRing !== false) {
                        window.MashoraRealtimeAlerts?.playIncomingRing();
                    }

                    window.MashoraRealtimeAlerts?.showDesktopNotification(title, message);

                    if (window.Flux?.toast) {
                        window.Flux.toast({ text: message, variant: 'success' });
                    }

                    return true;
                },
            };
        </script>
    @endpush
@endonce
