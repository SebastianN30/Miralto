<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWalletTransactionRequest;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\RedirectResponse;

class WalletTransactionController extends Controller
{
    public function store(StoreWalletTransactionRequest $request, Wallet $wallet): RedirectResponse
    {
        if (! $wallet->is_active) {
            return back()->withErrors(['amount' => 'No se pueden registrar movimientos en una billetera inactiva.']);
        }

        WalletTransaction::create([
            ...$request->validated(),
            'wallet_id' => $wallet->id,
            'user_id' => $request->user()->id,
        ]);

        return back();
    }

    public function destroy(WalletTransaction $transaction): RedirectResponse
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Solo administradores pueden eliminar transacciones.');
        }

        $transaction->delete();

        return back();
    }
}
