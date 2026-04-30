<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'cash_register_id',
    'user_id',
    'order_id',
    'type',
    'payment_method',
    'amount',
    'description',
])]
class CashMovement extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isManual(): bool
    {
        return in_array($this->type, ['income', 'expense'], true);
    }

    public function isAutomatic(): bool
    {
        return in_array($this->type, ['sale', 'refund'], true);
    }
}
