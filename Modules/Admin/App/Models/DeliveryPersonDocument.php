<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class DeliveryPersonDocument extends Model
{
    protected $table = 'delivery_person_documents';

    protected $fillable = [
        'delivery_person_id',
        'document_type',
        'document_number_encrypted',
        'document_number_masked',
        'front_image_path',
        'back_image_path',
        'expiry_date',
        'verification_status',
        'verification_notes',
        'verified_by_admin_id',
        'verified_at',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
    ];

    protected $hidden = [
        'document_number_encrypted',
        'front_image_path',
        'back_image_path',
    ];

    public function deliveryPerson(): BelongsTo
    {
        return $this->belongsTo(DeliveryPerson::class);
    }

    public function verifiedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'verified_by_admin_id');
    }

    public static function maskNumber(string $plain): string
    {
        $clean = trim($plain);
        $len = strlen($clean);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return str_repeat('*', $len - 4) . substr($clean, -4);
    }

    public function setPlainDocumentNumber(string $plain): void
    {
        $clean = strtoupper(trim($plain));
        $this->document_number_encrypted = Crypt::encryptString($clean);
        $this->document_number_masked = self::maskNumber($clean);
    }

    public function getDecryptedDocumentNumber(): ?string
    {
        if (! $this->document_number_encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($this->document_number_encrypted);
        } catch (\Throwable) {
            return null;
        }
    }
}
