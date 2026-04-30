<?php

namespace App\Models;

use App\Observers\OrderObserver;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'cash_register_id',
    'total',
    'status',
    'payment_method',
    'payment_amount_1',
    'payment_method_2',
    'payment_amount_2',
    'notes',
])]
#[ObservedBy([OrderObserver::class])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'payment_amount_1' => 'decimal:2',
            'payment_amount_2' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(OrderLog::class)->latest();
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function hasSplitPayment(): bool
    {
        return $this->payment_method_2 !== null;
    }

    /**
     * Sum of all payment amounts registered.
     */
    public function totalPaid(): float
    {
        return (float) ($this->payment_amount_1 ?? 0) + (float) ($this->payment_amount_2 ?? 0);
    }

    /**
     * Remaining balance after registered payments.
     */
    public function remainingBalance(): float
    {
        $paid = $this->totalPaid();

        return $paid > 0 ? max(0, (float) $this->total - $paid) : 0;
    }

    /**
     * Recalculate and persist the order total from its items.
     */
    public function recalculateTotal(): void
    {
        $this->total = $this->items()->sum('subtotal');
        $this->save();
    }
}
