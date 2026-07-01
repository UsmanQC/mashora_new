<div x-data="patientPhoneField(@entangle('countryIso'), @entangle('phone'))" x-init="init()">
    <flux:field>
        <flux:label class="!text-zinc-900">{{ __('patient_auth.phone_label') }}</flux:label>
        <div wire:ignore>
            <input
                x-ref="phoneInput"
                type="tel"
                inputmode="tel"
                autocomplete="tel-national"
                dir="ltr"
                placeholder="051 234 5678"
                class="iti-phone-field w-full rounded-xl border border-[#10B981]/35 bg-white px-3 py-3 text-start text-base text-zinc-900 shadow-sm ring-1 ring-black/[0.04] transition placeholder:text-zinc-400 focus:border-mashora-brand focus:ring-2 focus:ring-mashora-brand/25"
            />
        </div>
    </flux:field>
</div>
