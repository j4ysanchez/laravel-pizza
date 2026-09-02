<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'pizza_id', 'quantity', 'unit_price'];

    public function order(): BelongsTo {
        return $this->belongsTo(Order::class);
    }

    public function pizza(): BelongsTo {
        return $this->belongsTo(Pizza::class);
    }

}
