<div
    id="patient-portal-nav-loader"
    class="fixed inset-0 z-[100] hidden items-center justify-center bg-[#10B981] transition-opacity duration-200"
    aria-live="polite"
    aria-busy="false"
    role="status"
    aria-label="{{ __('patient.brand') }}"
    data-patient-portal-loader
>
    @include('partials.patient-brand-logo', [
        'svgClass' => 'h-11 w-auto max-w-[11rem] object-contain sm:h-12',
        'onGreenChrome' => true,
    ])
</div>
