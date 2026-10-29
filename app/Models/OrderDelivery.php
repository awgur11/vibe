<?php

namespace App\Models;

use Database\Factories\OrderDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDelivery extends Model
{
    /** @use HasFactory<OrderDeliveryFactory> */
    use HasFactory;

    protected $fillable = ['order_id', 'delivery_method', 'delivery_city_ref', 'delivery_city', 'delivery_warehouse_ref', 'delivery_warehouse', 'pickup_address'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
