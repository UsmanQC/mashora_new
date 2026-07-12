@push('scripts')
    <script data-navigate-once>
        function paymentDeadlineTimer(isoExpires, expiredLabel) {
            return {
                label: '',
                interval: null,
                expiredLabel: expiredLabel || 'Expired',
                start() {
                    this.tick();
                    this.interval = setInterval(() => this.tick(), 1000);
                },
                tick() {
                    if (! isoExpires) {
                        this.label = '';

                        return;
                    }

                    const diff = new Date(isoExpires).getTime() - Date.now();

                    if (diff <= 0) {
                        this.label = this.expiredLabel;

                        if (this.interval) {
                            clearInterval(this.interval);
                            this.interval = null;
                        }

                        return;
                    }

                    const totalSeconds = Math.floor(diff / 1000);
                    const hours = Math.floor(totalSeconds / 3600);
                    const minutes = Math.floor((totalSeconds % 3600) / 60);
                    const seconds = totalSeconds % 60;

                    if (hours > 0) {
                        this.label = `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                    } else {
                        this.label = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                    }
                },
            };
        }
    </script>
@endpush
