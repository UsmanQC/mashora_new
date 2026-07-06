<?php

namespace App\Models;

use App\Services\BankAccountAttachmentService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'doctor_id',
        'account_holder_name',
        'account_number',
        'iban_number',
        'attachment_path',
    ];

    /**
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function attachmentUrl(): ?string
    {
        return app(BankAccountAttachmentService::class)->url($this->attachment_path);
    }

    public function attachmentIsImage(): bool
    {
        return app(BankAccountAttachmentService::class)->isImage($this->attachment_path);
    }

    public function attachmentFilename(): ?string
    {
        return app(BankAccountAttachmentService::class)->filename($this->attachment_path);
    }
}
