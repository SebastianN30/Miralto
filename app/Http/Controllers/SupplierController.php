<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(): Response
    {
        $suppliers = Supplier::orderByRaw('is_active DESC')
            ->orderBy('name')
            ->get();

        return Inertia::render('suppliers/Index', [
            'suppliers' => $suppliers,
            'stats' => [
                'total' => $suppliers->count(),
                'active' => $suppliers->where('is_active', true)->count(),
                'inactive' => $suppliers->where('is_active', false)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:suppliers,name'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'El nombre del proveedor es obligatorio.',
            'name.unique' => 'Ya existe un proveedor con ese nombre.',
            'email.email' => 'El correo electrónico no es válido.',
        ]);

        Supplier::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Proveedor \"{$validated['name']}\" creado."]);

        return back();
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:suppliers,name,'.$supplier->id],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ], [
            'name.required' => 'El nombre del proveedor es obligatorio.',
            'name.unique' => 'Ya existe un proveedor con ese nombre.',
            'email.email' => 'El correo electrónico no es válido.',
        ]);

        $supplier->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Proveedor \"{$supplier->name}\" actualizado."]);

        return back();
    }

    public function destroy(Request $request, Supplier $supplier): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $name = $supplier->name;
        $supplier->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Proveedor \"{$name}\" eliminado."]);

        return back();
    }
}
