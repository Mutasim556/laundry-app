<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryPersonPostcode extends Model
{
    protected $table = 'delivery_person_postcodes';

    protected $fillable = [
        'delivery_person_id',
        'postcode',
        'postcode_compact',
    ];

    public function deliveryPerson(): BelongsTo
    {
        return $this->belongsTo(DeliveryPerson::class);
    }
}
