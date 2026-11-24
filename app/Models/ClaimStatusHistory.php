<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimStatusHistory extends Model
{
    protected $table = 'claim_status_history';

    protected $fillable = [
        'claim_id',
        'from_status',
        'to_status',
        'actor_type',
        'actor_id',
        'actor_name',
        'note',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(CustomerServiceClaim::class, 'claim_id');
    }
}
