<?php

use App\Http\Controllers\WalletTransactionController;
use App\Http\Controllers\CashMovementController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\DailySalesController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WaiterController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TableController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('sales', DailySalesController::class)->name('sales.index');

    Route::get('waiter', [WaiterController::class, 'index'])->name('waiter.index');
    Route::get('waiter/create', [WaiterController::class, 'create'])->name('waiter.create');
    Route::post('waiter', [WaiterController::class, 'store'])->name('waiter.store');
    Route::get('waiter/{order}', [WaiterController::class, 'show'])->name('waiter.show');
    Route::post('waiter/{order}/items', [WaiterController::class, 'addItems'])->name('waiter.add-items');

    Route::resource('orders', OrderController::class);
    Route::post('orders/{order}/split', [OrderController::class, 'split'])->name('orders.split');
    Route::post('orders/{order}/items', [OrderController::class, 'addItems'])->name('orders.add-items');

    Route::resource('categories', CategoryController::class)->except(['show', 'create', 'edit']);
    Route::resource('tables', TableController::class)->except(['show', 'create', 'edit']);
    Route::resource('suppliers', SupplierController::class)->except(['show', 'create', 'edit']);

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

    Route::get('wallets', [WalletController::class, 'index'])->name('wallets.index');
    Route::post('wallets', [WalletController::class, 'store'])->name('wallets.store');
    Route::get('wallets/{wallet}', [WalletController::class, 'show'])->name('wallets.show');
    Route::patch('wallets/{wallet}', [WalletController::class, 'update'])->name('wallets.update');
    Route::post('wallets/{wallet}/transactions', [WalletTransactionController::class, 'store'])->name('wallets.transactions.store');
    Route::delete('wallets/transactions/{transaction}', [WalletTransactionController::class, 'destroy'])->name('wallets.transactions.destroy');
});

require __DIR__.'/settings.php';
