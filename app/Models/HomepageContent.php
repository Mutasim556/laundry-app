<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admin\Models\Admin;

class HomepageContent extends Model
{
    protected $table = 'homepage_contents';

    protected $fillable = [
        'slug',
        'draft_payload',
        'published_payload',
        'last_draft_saved_by_admin_id',
        'last_draft_saved_at',
        'published_by_admin_id',
        'published_at',
        'current_version_number',
    ];

    protected $casts = [
        'draft_payload' => 'array',
        'published_payload' => 'array',
        'last_draft_saved_at' => 'datetime',
        'published_at' => 'datetime',
        'current_version_number' => 'integer',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(HomepageContentVersion::class, 'homepage_content_id')->orderByDesc('version_number');
    }

    public function lastDraftSavedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'last_draft_saved_by_admin_id');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'published_by_admin_id');
    }
}
