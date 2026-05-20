<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TableController extends Controller
{
    public function index(): Response
    {
        $tables = Table::withCount(['orders' => fn ($q) => $q->where('status', 'pending')])
            ->orderByRaw('is_active DESC')
            ->orderBy('name')
            ->get();

        return Inertia::render('tables/Index', [
            'tables' => $tables,
            'stats' => [
                'total' => $tables->count(),
                'active' => $tables->where('is_active', true)->count(),
                'inactive' => $tables->where('is_active', false)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:tables,name'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'zone' => ['nullable', 'string', 'max:100'],
        ], [
            'name.required' => 'El nombre de la mesa es obligatorio.',
            'name.unique' => 'Ya existe una mesa con ese nombre.',
            'capacity.integer' => 'La capacidad debe ser un número.',
            'capacity.min' => 'La capacidad mínima es 1.',
        ]);

        Table::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Mesa \"{$validated['name']}\" creada."]);

        return back();
    }

    public function update(Request $request, Table $table): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:tables,name,'.$table->id],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'zone' => ['nullable', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ], [
            'name.required' => 'El nombre de la mesa es obligatorio.',
            'name.unique' => 'Ya existe una mesa con ese nombre.',
        ]);

        $table->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Mesa \"{$table->name}\" actualizada."]);

        return back();
    }

    public function destroy(Request $request, Table $table): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        if ($table->orders()->where('status', 'pending')->exists()) {
            return back()->withErrors(['table' => "No puedes eliminar \"{$table->name}\" porque tiene pedidos pendientes."]);
        }

        $name = $table->name;
        $table->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Mesa \"{$name}\" eliminada."]);

        return back();
    }
}
