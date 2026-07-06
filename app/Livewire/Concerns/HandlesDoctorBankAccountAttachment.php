<?php

namespace App\Livewire\Concerns;

use App\Models\BankAccount;
use App\Services\BankAccountAttachmentService;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

trait HandlesDoctorBankAccountAttachment
{
    use WithFileUploads;

    /** @var TemporaryUploadedFile|null */
    public $attachment = null;

    public ?string $existingAttachmentPath = null;

    public bool $removeExistingAttachment = false;

    protected function mountBankAccountAttachment(?BankAccount $account): void
    {
        $this->existingAttachmentPath = $account?->attachment_path;
    }

    /**
     * @return array<string, list<string|int>>
     */
    protected function bankAttachmentValidationRules(): array
    {
        return [
            'attachment' => app(BankAccountAttachmentService::class)->validationRules(),
        ];
    }

    protected function syncBankAccountAttachment(BankAccount $account): void
    {
        $attachmentService = app(BankAccountAttachmentService::class);

        if ($this->removeExistingAttachment) {
            $attachmentService->delete($account->attachment_path);
            $account->forceFill(['attachment_path' => null])->save();
            $this->existingAttachmentPath = null;
            $this->removeExistingAttachment = false;
        }

        if ($this->attachment instanceof TemporaryUploadedFile) {
            $path = $attachmentService->store($this->attachment, $account->attachment_path);
            $account->forceFill(['attachment_path' => $path])->save();
            $this->existingAttachmentPath = $path;
            $this->attachment = null;
            $this->removeExistingAttachment = false;
        }
    }

    public function removeBankAttachment(): void
    {
        $this->attachment = null;
        $this->removeExistingAttachment = true;
        $this->resetErrorBag('attachment');
    }

    protected function existingAttachmentUrl(): ?string
    {
        if ($this->removeExistingAttachment) {
            return null;
        }

        return app(BankAccountAttachmentService::class)->url($this->existingAttachmentPath);
    }

    protected function existingAttachmentIsImage(): bool
    {
        if ($this->removeExistingAttachment) {
            return false;
        }

        return app(BankAccountAttachmentService::class)->isImage($this->existingAttachmentPath);
    }

    protected function existingAttachmentFilename(): ?string
    {
        if ($this->removeExistingAttachment) {
            return null;
        }

        return app(BankAccountAttachmentService::class)->filename($this->existingAttachmentPath);
    }
}
