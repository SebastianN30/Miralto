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
    'table_id',
    'table_name',
    'total',
    'status',
    'payment_method',
    'payment_amount_1',
    'payment_method_2',
    'payment_amount_2',
    'notes',
    'service_charge',
    'service_charge_percentage',
    'service_charge_amount',
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
            'service_charge' => 'boolean',
            'service_charge_percentage' => 'decimal:2',
            'service_charge_amount' => 'decimal:2',
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

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
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

    /** Computed service charge amount using the stored percentage (default 10%). */
    public function computedServiceChargeAmount(): float
    {
        $pct = (float) ($this->service_charge_percentage ?? 10);

        return round((float) $this->items()->sum('subtotal') * ($pct / 100), 2);
    }

    /**
     * Recalculate and persist the order total from its items.
     * Preserves the service charge and percentage if already applied.
     */
    public function recalculateTotal(): void
    {
        $subtotal = (float) $this->items()->sum('subtotal');

        if ($this->service_charge) {
            $pct = (float) ($this->service_charge_percentage ?? 10);
            $serviceAmount = round($subtotal * ($pct / 100), 2);
            $this->service_charge_amount = $serviceAmount;
            $this->total = round($subtotal + $serviceAmount, 2);
        } else {
            $this->total = $subtotal;
        }

        $this->save();
    }
}
