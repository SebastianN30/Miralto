<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['category_id', 'printer_id', 'name', 'description', 'price', 'cost', 'stock', 'is_active'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function printer(): BelongsTo
    {
        return $this->belongsTo(Printer::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'product_ingredients')
            ->using(ProductIngredient::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Sum of (ingredient.cost_per_unit × pivot.quantity) for all linked ingredients.
     */
    public function ingredientCost(): float
    {
        return (float) $this->ingredients->sum(
            fn (Ingredient $i) => $i->cost_per_unit * $i->pivot->quantity,
        );
    }

    /**
     * Calculate profit margin percentage based on price and cost.
     */
    public function marginPercentage(): ?float
    {
        if (! $this->cost || $this->cost == 0) {
            return null;
        }

        return round((($this->price - $this->cost) / $this->price) * 100, 2);
    }
}
