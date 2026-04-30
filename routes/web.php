<?php

use App\Http\Controllers\CashMovementController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

/* Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home'); */

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('orders', OrderController::class);
    Route::post('orders/{order}/split', [OrderController::class, 'split'])->name('orders.split');

    Route::resource('products', ProductController::class)->except(['show']);
    Route::post('products/{id}/restore', [ProductController::class, 'restore'])->name('products.restore');

    Route::post('ingredients', [IngredientController::class, 'store'])->name('ingredients.store');
    Route::patch('ingredients/{ingredient}', [IngredientController::class, 'update'])->name('ingredients.update');
    Route::delete('ingredients/{ingredient}', [IngredientController::class, 'destroy'])->name('ingredients.destroy');

    Route::get('cash', [CashRegisterController::class, 'index'])->name('cash.index');
    Route::post('cash', [CashRegisterController::class, 'store'])->name('cash.store');
    Route::get('cash/{cash}', [CashRegisterController::class, 'show'])->name('cash.show');
    Route::patch('cash/{cash}/close', [CashRegisterController::class, 'close'])->name('cash.close');
    Route::post('cash/{cash}/movements', [CashMovementController::class, 'store'])->name('cash.movements.store');
    Route::delete('cash/movements/{movement}', [CashMovementController::class, 'destroy'])->name('cash.movements.destroy');
});

require __DIR__.'/settings.php';
