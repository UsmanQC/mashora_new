<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@25.2.1/build/css/intlTelInput.css" data-navigate-track>
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
    .iti--allow-dropdown .iti__country-container .iti__selected-country:hover {
        background: rgb(244 244 245);
    }
    .iti--allow-dropdown .iti__country-container .iti__selected-country:focus-visible {
        outline: none;
        box-shadow: inset 0 0 0 2px rgb(16 185 129 / 0.25);
    }
    .iti__arrow {
        margin-inline-start: 0.35rem;
        border-top-color: rgb(113 113 122);
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
        background: #ffffff;
        color: #18181b;
    }
    .iti__country-list::-webkit-scrollbar {
        width: 10px;
    }
    .iti__country-list::-webkit-scrollbar-track {
        background: rgb(244 244 245);
        border-radius: 9999px;
    }
    .iti__country-list::-webkit-scrollbar-thumb {
        background: rgb(161 161 170);
        border-radius: 9999px;
        border: 2px solid rgb(244 244 245);
    }
    .iti__search-input {
        border: 1px solid rgb(212 212 216 / 1) !important;
        border-radius: 0.55rem !important;
        margin: 0.5rem !important;
        width: calc(100% - 1rem) !important;
        height: 2.25rem;
        padding-inline: 0.7rem;
        font-size: 0.92rem;
        color: #18181b !important;
        background: #ffffff !important;
    }
    .iti__search-input:focus {
        outline: none;
        border-color: rgb(16 185 129 / 0.7) !important;
        box-shadow: 0 0 0 3px rgb(16 185 129 / 0.15);
    }
    .iti__country {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        padding: 0.58rem 0.75rem;
        font-size: 0.95rem;
        color: #18181b;
    }
    .iti__country.iti__highlight {
        background-color: rgb(16 185 129 / 0.08);
    }
    .iti__country-name {
        display: none !important;
    }
    .iti__dial-code {
        color: #18181b !important;
        font-weight: 600;
    }
    .iti-phone-field {
        padding-inline-start: 4.25rem !important;
        padding-inline-end: 0.75rem !important;
    }
    .iti__selected-dial-code {
        display: none !important;
    }
</style>
