<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@25.2.1/build/js/intlTelInput.min.js" data-navigate-track></script>
<script data-navigate-once>
    function patientPhoneField(countryIso, phone) {
        return {
            iti: null,
            countryIso,
            phone,
            listenersBound: false,
            init() {
                this.$nextTick(() => this.setupIti());

                document.addEventListener('livewire:navigated', () => {
                    if (this.$refs.phoneInput?.isConnected) {
                        this.$nextTick(() => this.setupIti());
                    }
                });
            },
            waitForIntlTelInput() {
                return new Promise((resolve) => {
                    if (window.intlTelInput) {
                        resolve();

                        return;
                    }

                    const poll = () => {
                        if (window.intlTelInput) {
                            resolve();

                            return;
                        }

                        requestAnimationFrame(poll);
                    };

                    poll();
                });
            },
            async setupIti() {
                await this.waitForIntlTelInput();

                const input = this.$refs.phoneInput;
                if (! input) {
                    return;
                }

                if (this.iti) {
                    this.iti.destroy();
                    this.iti = null;
                }

                this.iti = window.intlTelInput(input, {
                    initialCountry: (this.countryIso || 'SA').toLowerCase(),
                    preferredCountries: ['sa', 'eg'],
                    separateDialCode: false,
                    nationalMode: true,
                    strictMode: false,
                });

                if (! this.listenersBound) {
                    const syncToLivewire = () => {
                        const data = this.iti.getSelectedCountryData();
                        this.countryIso = (data.iso2 || 'sa').toUpperCase();
                        this.phone = (input.value || '').replace(/\D/g, '');
                    };

                    input.addEventListener('input', syncToLivewire);
                    input.addEventListener('blur', syncToLivewire);
                    input.addEventListener('countrychange', syncToLivewire);

                    const form = this.$el.closest('form');
                    if (form) {
                        form.addEventListener('submit', syncToLivewire, { capture: true });
                    }

                    this.listenersBound = true;
                }

                const data = this.iti.getSelectedCountryData();
                this.countryIso = (data.iso2 || 'sa').toUpperCase();
                this.phone = (input.value || '').replace(/\D/g, '');
            },
        };
    }
</script>
