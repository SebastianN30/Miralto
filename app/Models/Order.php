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
    'employee_id',
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
    'tax',
    'tax_amount',
])]
#[ObservedBy([OrderObserver::class])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Fixed tax rate applied when `tax` is enabled on an order.
     * Keep in sync with the frontend if this value ever changes.
     */
    public const TAX_PERCENTAGE = 3.5;

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'payment_amount_1' => 'decimal:2',
            'payment_amount_2' => 'decimal:2',
            'service_charge' => 'boolean',
            'service_charge_percentage' => 'decimal:2',
            'service_charge_amount' => 'decimal:2',
            'tax' => 'boolean',
            'tax_amount' => 'decimal:2',
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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
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

    /** Computed tax amount using the fixed tax rate. */
    public function computedTaxAmount(): float
    {
        return round((float) $this->items()->sum('subtotal') * (self::TAX_PERCENTAGE / 100), 2);
    }

    /**
     * Recalculate and persist the order total from its items.
     * Preserves the service charge and tax, calculated independently over the same subtotal.
     */
    public function recalculateTotal(): void
    {
        $subtotal = (float) $this->items()->sum('subtotal');
        $total = $subtotal;

        if ($this->service_charge) {
            $pct = (float) ($this->service_charge_percentage ?? 10);
            $serviceAmount = round($subtotal * ($pct / 100), 2);
            $this->service_charge_amount = $serviceAmount;
            $total += $serviceAmount;
        }

        if ($this->tax) {
            $taxAmount = round($subtotal * (self::TAX_PERCENTAGE / 100), 2);
            $this->tax_amount = $taxAmount;
            $total += $taxAmount;
        }

        $this->total = round($total, 2);

        $this->save();
    }
}
