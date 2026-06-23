@props([
    'readyLabel' => null,
    'startsInLabel' => null,
])

@php
    $readyLabel ??= __('doctor.appointments.ready_to_start');
    $startsInLabel ??= __('doctor.appointments.starts_in');
@endphp

@push('scripts')
    <script data-navigate-once>
        function appointmentStartTimer(isoStart) {
            return {
                label: '',
                ariaLabel: '',
                showPrefix: true,
                ready: ! isoStart || new Date(isoStart).getTime() <= Date.now(),
                interval: null,
                labels: {
                    ready: @json($readyLabel),
                    startsIn: @json($startsInLabel),
                },
                start() {
                    this.tick();
                    this.interval = setInterval(() => this.tick(), 1000);
                },
                tick() {
                    if (! isoStart) {
                        this.label = '';
                        this.ariaLabel = '';
                        this.showPrefix = true;
                        this.ready = true;

                        return;
                    }

                    const target = new Date(isoStart);
                    const diff = target.getTime() - Date.now();

                    if (diff <= 0) {
                        this.label = this.labels.ready;
                        this.ariaLabel = this.labels.ready;
                        this.showPrefix = false;
                        this.ready = true;

                        if (this.interval) {
                            clearInterval(this.interval);
                            this.interval = null;
                        }

                        return;
                    }

                    this.showPrefix = true;
                    this.ready = false;

                    const totalSeconds = Math.floor(diff / 1000);
                    const days = Math.floor(totalSeconds / 86400);
                    const hours = Math.floor((totalSeconds % 86400) / 3600);
                    const minutes = Math.floor((totalSeconds % 3600) / 60);
                    const seconds = totalSeconds % 60;

                    let timeLabel;

                    if (days > 0) {
                        timeLabel = `${days}d ${hours}h`;
                    } else if (hours > 0) {
                        timeLabel = `${hours}h ${String(minutes).padStart(2, '0')}m`;
                    } else {
                        timeLabel = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                    }

                    this.label = timeLabel;
                    this.ariaLabel = this.labels.startsIn.replace(':time', timeLabel);
                },
            };
        }
    </script>
@endpush
