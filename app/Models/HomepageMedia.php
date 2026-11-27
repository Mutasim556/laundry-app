<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Models\Admin;

class HomepageMedia extends Model
{
    protected $table = 'homepage_media';

    protected $fillable = [
        'filename',
        'disk',
        'path',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'alt_text',
        'uploaded_by_admin_id',
        'archived_at',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'archived_at' => 'datetime',
    ];

    public function uploadedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'uploaded_by_admin_id');
    }

    public function publicUrl(): string
    {
        return Storage::disk($this->disk ?: 'public')->url($this->path);
    }

    public function toSafeArray(bool $inUse = false): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'path' => $this->path,
            'url' => $this->publicUrl(),
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'width' => $this->width,
            'height' => $this->height,
            'alt_text' => $this->alt_text,
            'in_use' => $inUse,
            'created_at' => $this->created_at?->format('d M Y, H:i'),
        ];
    }
}
