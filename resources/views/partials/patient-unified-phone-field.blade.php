<div x-data="patientPhoneField(@entangle('countryIso'), @entangle('phone'))" x-init="init()">
    <flux:field>
        <flux:label>{{ __('patient_auth.phone_label') }}</flux:label>
        <div wire:ignore>
            <input
                x-ref="phoneInput"
                type="tel"
                inputmode="tel"
                autocomplete="tel-national"
                placeholder="051 234 5678"
                class="iti-phone-field w-full rounded-xl border border-[#3c5cf7]/35 bg-white px-3 py-3 text-base text-zinc-900 shadow-sm ring-1 ring-black/[0.04] transition placeholder:text-zinc-400 focus:border-mashora-brand focus:ring-2 focus:ring-mashora-brand/25"
            />
        </div>
    </flux:field>
</div>

@once
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@25.2.1/build/css/intlTelInput.css">
        <style>
            .iti {
                width: 100%;
            }
            .iti--allow-dropdown .iti__country-container .iti__selected-country {
                padding-inline: 0.75rem;
                border-inline-end: 1px solid rgb(228 228 231 / 0.9);
                background: rgb(250 250 250 / 0.9);
                border-start-start-radius: 0.75rem;
                border-end-start-radius: 0.75rem;
            }
            .iti__country-list {
                z-index: 60;
                width: 100%;
                min-width: 100%;
                border-radius: 0.85rem;
                border: 1px solid rgb(228 228 231 / 1);
                box-shadow: 0 14px 34px -18px rgb(9 18 58 / 0.35);
                max-height: 260px;
                overflow-y: auto;
                overflow-x: hidden;
                margin-top: 0.4rem;
            }
            .iti__country-list::-webkit-scrollbar {
                width: 10px;
            }
            .iti__search-input {
                border: 1px solid rgb(212 212 216 / 1) !important;
                border-radius: 0.55rem !important;
                margin: 0.5rem !important;
                width: calc(100% - 1rem) !important;
                height: 2.25rem;
                padding-inline: 0.7rem;
                font-size: 0.92rem;
            }
            .iti-phone-field {
                padding-left: 4.25rem !important;
            }
            .iti__selected-dial-code {
                display: none !important;
            }
        </style>
    @endpush
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@25.2.1/build/js/intlTelInput.min.js"></script>
        <script>
            function patientPhoneField(countryIso, phone) {
                return {
                    iti: null,
                    countryIso,
                    phone,
                    init() {
                        const input = this.$refs.phoneInput;
                        this.iti = window.intlTelInput(input, {
                            initialCountry: (this.countryIso || 'SA').toLowerCase(),
                            preferredCountries: ['sa', 'eg'],
                            separateDialCode: false,
                            nationalMode: true,
                            strictMode: false,
                        });

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

                        syncToLivewire();
                    },
                };
            }
        </script>
    @endpush
@endonce
