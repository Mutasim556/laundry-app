<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\Admin;

class HomepageContentVersion extends Model
{
    public $timestamps = false;

    protected $table = 'homepage_content_versions';

    protected $fillable = [
        'homepage_content_id',
        'version_number',
        'action',
        'section_key',
        'payload',
        'notes',
        'created_by_admin_id',
        'created_at',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(HomepageContent::class, 'homepage_content_id');
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }
}
