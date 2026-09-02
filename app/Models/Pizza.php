<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pizza extends Model
{
    /** @use HasFactory<\Database\Factories\PizzaFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'price', 'image'];

    public function toppings(): BelongsToMany {
        return $this->belongsToMany(Topping::class);
    }

    public function orderItems(): HasMany {
        return $this->hasMany(OrderItem::class);
    }
}
