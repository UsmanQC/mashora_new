@once
    @push('scripts')
        <script>
            window.MashoraRealtimeAlerts = window.MashoraRealtimeAlerts || {
                ringIntervalId: null,

                playIncomingRing() {
                    this.stopIncomingRing();

                    try {
                        const AudioContext = window.AudioContext || window.webkitAudioContext;
                        if (!AudioContext) {
                            return;
                        }

                        const ctx = new AudioContext();
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
        </script>
    @endpush
@endonce
