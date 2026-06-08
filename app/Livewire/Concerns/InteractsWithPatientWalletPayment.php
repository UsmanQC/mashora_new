<?php

namespace App\Livewire\Concerns;

use App\Models\User;
use App\Services\PatientWalletService;

trait InteractsWithPatientWalletPayment
{
    public bool $useWallet = false;

    protected function bootPatientWalletPayment(float $storedWalletAmount = 0.0): void
    {
        $this->patientWalletService()->ensureWallet($this->authenticatedPatient());

        $this->useWallet = $storedWalletAmount > 0 || $this->patientWalletBalance() > 0;
    }

    public function patientWalletBalance(): float
    {
        return $this->patientWalletService()->balance($this->authenticatedPatient());
    }

    public function walletAppliedToward(float $total): float
    {
        if (! $this->useWallet) {
            return 0.0;
        }

        return round(min($this->patientWalletBalance(), $total), 2);
    }

    public function amountDueAfterWallet(float $total): float
    {
        return max(0.0, round($total - $this->walletAppliedToward($total), 2));
    }

    protected function authenticatedPatient(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    protected function patientWalletService(): PatientWalletService
    {
        return app(PatientWalletService::class);
    }
}
