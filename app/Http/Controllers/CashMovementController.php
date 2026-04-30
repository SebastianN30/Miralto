<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCashMovementRequest;
use App\Models\CashMovement;
use App\Models\CashRegister;
use Illuminate\Http\RedirectResponse;

class CashMovementController extends Controller
{
    public function store(StoreCashMovementRequest $request, CashRegister $cash): RedirectResponse
    {
        if ($cash->isClosed()) {
            return back()->withErrors(['amount' => 'No se pueden registrar movimientos en una caja cerrada.']);
        }

        CashMovement::create([
            'cash_register_id' => $cash->id,
            'user_id' => $request->user()->id,
            'type' => $request->type,
            'payment_method' => $request->payment_method ?? 'cash',
            'amount' => $request->amount,
            'description' => $request->description,
        ]);

        return back();
    }

    public function destroy(CashMovement $movement): RedirectResponse
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Solo administradores pueden eliminar movimientos.');
        }

        if ($movement->isAutomatic()) {
            return back()->withErrors(['movement' => 'No se pueden eliminar movimientos automáticos (ventas/devoluciones).']);
        }

        if ($movement->cashRegister->isClosed()) {
            return back()->withErrors(['movement' => 'La caja está cerrada — no se pueden eliminar movimientos.']);
        }

        $movement->delete();

        return back();
    }
}
