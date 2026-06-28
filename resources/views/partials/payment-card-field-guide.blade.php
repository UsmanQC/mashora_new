<div class="payment-card-field-guide mb-3 rounded-xl border border-zinc-200/90 bg-zinc-50/90 px-3 py-3 sm:px-4">
    <p class="text-sm font-semibold text-zinc-900">{{ __('patient_booking.payment_card_details') }}</p>
    <p class="mt-1 text-xs text-zinc-600">{{ __('patient_booking.payment_card_details_hint') }}</p>
    <ul class="mt-3 grid gap-2 text-xs text-zinc-700 sm:grid-cols-2 sm:text-sm">
        <li class="flex items-center gap-2 rounded-lg border border-zinc-200/80 bg-white px-3 py-2">
            <span class="font-semibold text-zinc-500">{{ __('patient_booking.payment_label_card_holder') }}</span>
        </li>
        <li class="flex items-center gap-2 rounded-lg border border-zinc-200/80 bg-white px-3 py-2">
            <span class="font-semibold text-zinc-500">{{ __('patient_booking.payment_label_card_number') }}</span>
        </li>
        <li class="flex items-center gap-2 rounded-lg border border-zinc-200/80 bg-white px-3 py-2">
            <span class="font-semibold text-zinc-500">{{ __('patient_booking.payment_label_expiry') }}</span>
        </li>
        <li class="flex items-center gap-2 rounded-lg border border-zinc-200/80 bg-white px-3 py-2">
            <span class="font-semibold text-zinc-500">{{ __('patient_booking.payment_label_cvv') }}</span>
        </li>
    </ul>
</div>
