<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public const array STATUSES = ['new' => 'Новый', 'processing' => 'В обработке', 'completed' => 'Выполнен', 'cancelled' => 'Отменён'];

    public const array CUSTOMER_TYPES = ['individual' => 'Физическое лицо', 'company' => 'Юридическое лицо'];

    public const array PAYMENT_METHODS = ['cash' => 'Оплата готівкою', 'bank_transfer' => 'Оплата за реквізитами'];

    public const array DELIVERY_METHODS = ['nova_poshta' => 'Новая почта', 'pickup' => 'Самовывоз'];

    protected $fillable = ['client_id', 'customer_type', 'name', 'phone', 'email', 'company_name', 'legal_address', 'tax_id', 'kpp', 'payment_method', 'total', 'status'];

    protected function casts(): array
    {
        return ['total' => 'decimal:2'];
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(OrderDelivery::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
