<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'opened_at',
    'closed_at',
    'opening_amount',
    'closing_amount',
    'opening_notes',
    'closing_notes',
    'status',
])]
class CashRegister extends Model
{
    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_amount' => 'decimal:2',
            'closing_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Sum of cash income (manual ingresos + cash sales) registered in this session.
     */
    public function totalCashIn(): float
    {
        return (float) $this->movements()
            ->whereIn('type', ['income', 'sale'])
            ->where(function ($q) {
                $q->where('payment_method', 'cash')->orWhereNull('payment_method');
            })
            ->sum('amount');
    }

    /**
     * Sum of cash outflows (manual egresos + cash refunds).
     */
    public function totalCashOut(): float
    {
        return (float) $this->movements()
            ->whereIn('type', ['expense', 'refund'])
            ->where(function ($q) {
                $q->where('payment_method', 'cash')->orWhereNull('payment_method');
            })
            ->sum('amount');
    }

    /**
     * Expected cash on hand at this moment: opening + cash in − cash out.
     */
    public function expectedCash(): float
    {
        return (float) $this->opening_amount + $this->totalCashIn() - $this->totalCashOut();
    }

    /**
     * Difference between declared closing_amount and expected (only after closing).
     */
    public function difference(): ?float
    {
        if ($this->closing_amount === null) {
            return null;
        }

        return (float) $this->closing_amount - $this->expectedCash();
    }

    public function totalSales(): float
    {
        return (float) $this->movements()->where('type', 'sale')->sum('amount');
    }

    /**
     * Cash-only sales — what physically enters the box.
     */
    public function totalSalesCash(): float
    {
        return (float) $this->movements()
            ->where('type', 'sale')
            ->where('payment_method', 'cash')
            ->sum('amount');
    }

    /**
     * Sales paid with non-cash methods (transfer, card) — don't affect the physical box.
     */
    public function totalSalesOther(): float
    {
        return (float) $this->movements()
            ->where('type', 'sale')
            ->whereIn('payment_method', ['transfer', 'card'])
            ->sum('amount');
    }

    /**
     * Sales totals broken down by payment method.
     *
     * @return array{cash: float, transfer: float, card: float}
     */
    public function salesByPaymentMethod(): array
    {
        $rows = $this->movements()
            ->where('type', 'sale')
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->toArray();

        return [
            'cash' => (float) ($rows['cash'] ?? 0),
            'transfer' => (float) ($rows['transfer'] ?? 0),
            'card' => (float) ($rows['card'] ?? 0),
        ];
    }

    public function totalIncome(): float
    {
        return (float) $this->movements()->where('type', 'income')->sum('amount');
    }

    public function totalExpense(): float
    {
        return (float) $this->movements()->where('type', 'expense')->sum('amount');
    }

    public function totalRefund(): float
    {
        return (float) $this->movements()->where('type', 'refund')->sum('amount');
    }
}
