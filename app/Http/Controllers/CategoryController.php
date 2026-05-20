<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::withCount('products')
            ->orderBy('name')
            ->get();

        return Inertia::render('categories/Index', [
            'categories' => $categories,
            'stats' => [
                'total' => $categories->count(),
                'active' => $categories->where('is_active', true)->count(),
                'inactive' => $categories->where('is_active', false)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.unique' => 'Ya existe una categoría con ese nombre.',
            'name.max' => 'El nombre no puede superar 100 caracteres.',
        ]);

        Category::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Categoría \"{$validated['name']}\" creada."]);

        return back();
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name,'.$category->id],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
        ], [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.unique' => 'Ya existe una categoría con ese nombre.',
        ]);

        $category->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Categoría \"{$category->name}\" actualizada."]);

        return back();
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        if ($category->products()->exists()) {
            return back()->withErrors(['category' => "No puedes eliminar \"{$category->name}\" porque tiene productos asociados. Desactívala en su lugar."]);
        }

        $name = $category->name;
        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Categoría \"{$name}\" eliminada."]);

        return back();
    }
}
