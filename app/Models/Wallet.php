<?php

namespace App\Models;

use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'type',
    'account_identifier',
    'initial_balance',
    'is_active',
    'notes',
])]
class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'initial_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Sum of all inbound transactions (income + payment). */
    public function totalInbound(): float
    {
        return (float) $this->transactions()
            ->whereIn('type', ['income', 'payment'])
            ->sum('amount');
    }

    /** Sum of all outbound transactions (expense). */
    public function totalOutbound(): float
    {
        return (float) $this->transactions()
            ->where('type', 'expense')
            ->sum('amount');
    }

    /** Current balance: initial + inbound − outbound. */
    public function currentBalance(): float
    {
        return (float) $this->initial_balance + $this->totalInbound() - $this->totalOutbound();
    }

    public function totalIncome(): float
    {
        return (float) $this->transactions()->where('type', 'income')->sum('amount');
    }

    public function totalPayments(): float
    {
        return (float) $this->transactions()->where('type', 'payment')->sum('amount');
    }

    public function totalExpenses(): float
    {
        return (float) $this->transactions()->where('type', 'expense')->sum('amount');
    }
}
