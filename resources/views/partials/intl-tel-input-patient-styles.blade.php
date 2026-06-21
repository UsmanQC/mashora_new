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
