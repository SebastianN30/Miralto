<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'position', 'role', 'user_id', 'is_active'])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    /** Roles de empleado. `waiter` y `cook` pueden tener usuario; el slug es el mismo `users.role`. */
    public const ROLES = ['waiter' => 'Mesero', 'cook' => 'Cocinero', 'other' => 'Otro'];

    /** Roles que admiten acceso al sistema. */
    public const LOGIN_ROLES = ['waiter', 'cook'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @param Builder<Employee> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
