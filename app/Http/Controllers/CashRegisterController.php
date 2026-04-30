<?php

namespace App\Http\Controllers;

use App\Http\Requests\CloseCashRegisterRequest;
use App\Http\Requests\StoreCashRegisterRequest;
use App\Models\CashRegister;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashRegisterController extends Controller
{
    public function index(Request $request): Response
    {
        $registers = CashRegister::with('user:id,name')
            ->withCount('movements')
            ->withSum(['movements as total_sales_cash' => fn ($q) => $q->where('type', 'sale')->where('payment_method', 'cash')], 'amount')
            ->withSum(['movements as total_sales_other' => fn ($q) => $q->where('type', 'sale')->whereIn('payment_method', ['transfer', 'card'])], 'amount')
            ->withSum(['movements as total_expense' => fn ($q) => $q->where('type', 'expense')], 'amount')
            ->latest('opened_at')
            ->paginate(15);

        $openRegister = CashRegister::open()->latest('opened_at')->first();

        return Inertia::render('cash/Index', [
            'registers' => $registers,
            'openRegister' => $openRegister?->only(['id', 'opened_at', 'opening_amount']),
            'stats' => [
                'total_sessions' => CashRegister::count(),
                'open_sessions' => CashRegister::where('status', 'open')->count(),
            ],
        ]);
    }

    public function store(StoreCashRegisterRequest $request): RedirectResponse
    {
        if (CashRegister::open()->exists()) {
            return back()->withErrors(['opening_amount' => 'Ya hay una caja abierta. Ciérrala antes de abrir otra.']);
        }

        $register = CashRegister::create([
            'user_id' => $request->user()->id,
            'opened_at' => now(),
            'opening_amount' => $request->opening_amount,
            'opening_notes' => $request->opening_notes,
            'status' => 'open',
        ]);

        return to_route('cash.show', $register);
    }

    public function show(CashRegister $cash): Response
    {
        $cash->load([
            'user:id,name',
            'movements' => fn ($q) => $q->latest('created_at'),
            'movements.user:id,name',
            'movements.order:id,total,status',
            'orders' => fn ($q) => $q->latest()->limit(50),
            'orders.user:id,name',
        ]);

        return Inertia::render('cash/Show', [
            'register' => $cash,
            'totals' => [
                'opening_amount' => (float) $cash->opening_amount,
                'closing_amount' => $cash->closing_amount !== null ? (float) $cash->closing_amount : null,

                // Cash only — affects the physical box
                'total_sales_cash' => $cash->totalSalesCash(),
                'total_income' => $cash->totalIncome(),
                'total_expense' => $cash->totalExpense(),
                'total_refund' => $cash->totalRefund(),

                // Non-cash sales — informational only
                'sales_by_method' => $cash->salesByPaymentMethod(),
                'total_sales_other' => $cash->totalSalesOther(),

                // Cash summary
                'expected_cash' => $cash->expectedCash(),
                'difference' => $cash->difference(),
                'cash_in' => $cash->totalCashIn(),
                'cash_out' => $cash->totalCashOut(),
            ],
        ]);
    }

    public function close(CloseCashRegisterRequest $request, CashRegister $cash): RedirectResponse
    {
        if ($cash->isClosed()) {
            return back()->withErrors(['closing_amount' => 'Esta caja ya está cerrada.']);
        }

        $cash->update([
            'closed_at' => now(),
            'closing_amount' => $request->closing_amount,
            'closing_notes' => $request->closing_notes,
            'status' => 'closed',
        ]);

        return to_route('cash.show', $cash);
    }
}
