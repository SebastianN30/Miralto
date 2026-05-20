<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWalletRequest;
use App\Http\Requests\UpdateWalletRequest;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function index(): Response
    {
        $wallets = Wallet::withCount('transactions')
            ->withSum(['transactions as total_inbound' => fn ($q) => $q->whereIn('type', ['income', 'payment'])], 'amount')
            ->withSum(['transactions as total_outbound' => fn ($q) => $q->where('type', 'expense')], 'amount')
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();

        $walletsWithBalance = $wallets->map(fn (Wallet $w) => array_merge(
            $w->toArray(),
            ['current_balance' => (float) $w->initial_balance + (float) ($w->total_inbound ?? 0) - (float) ($w->total_outbound ?? 0)]
        ));

        return Inertia::render('wallets/Index', [
            'wallets' => $walletsWithBalance,
            'stats' => [
                'total_wallets' => $wallets->count(),
                'active_wallets' => $wallets->where('is_active', true)->count(),
                'total_balance' => $wallets->where('is_active', true)->sum(fn (Wallet $w) => $w->currentBalance()),
            ],
        ]);
    }

    public function store(StoreWalletRequest $request): RedirectResponse
    {
        $wallet = Wallet::create($request->validated());

        return to_route('wallets.show', $wallet);
    }

    public function show(Wallet $wallet): Response
    {
        $wallet->load([
            'transactions' => fn ($q) => $q->with('user:id,name')->latest('transaction_date')->latest('id'),
        ]);

        return Inertia::render('wallets/Show', [
            'wallet' => $wallet,
            'totals' => [
                'current_balance' => $wallet->currentBalance(),
                'total_income' => $wallet->totalIncome(),
                'total_payments' => $wallet->totalPayments(),
                'total_expenses' => $wallet->totalExpenses(),
                'total_inbound' => $wallet->totalInbound(),
                'total_outbound' => $wallet->totalOutbound(),
            ],
        ]);
    }

    public function update(UpdateWalletRequest $request, Wallet $wallet): RedirectResponse
    {
        $wallet->update($request->validated());

        return back();
    }
}
