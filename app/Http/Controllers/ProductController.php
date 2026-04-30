<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Product::withTrashed()
            ->with(['category'])
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->category_id, fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->status === 'active', fn ($q) => $q->whereNull('deleted_at')->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->whereNull('deleted_at')->where('is_active', false))
            ->when($request->status === 'deleted', fn ($q) => $q->onlyTrashed())
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => Product::count(),
            'active' => Product::where('is_active', true)->count(),
            'inactive' => Product::where('is_active', false)->count(),
            'deleted' => Product::onlyTrashed()->count(),
        ];

        return Inertia::render('products/Index', [
            'products' => $query,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'stats' => $stats,
            'filters' => $request->only(['search', 'category_id', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('products/Create', [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'ingredients' => Ingredient::where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit', 'cost_per_unit']),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->safe()->except('ingredients'));

        $this->syncIngredients($product, $request->ingredients ?? []);

        return redirect()->route('products.index');
    }

    public function edit(Product $product): Response
    {
        $product->load('ingredients');

        return Inertia::render('products/Edit', [
            'product' => $product,
            'productIngredients' => $product->ingredients->map(fn (Ingredient $i) => [
                'ingredient_id' => $i->id,
                'name' => $i->name,
                'unit' => $i->unit,
                'cost_per_unit' => $i->cost_per_unit,
                'quantity' => $i->pivot->quantity,
            ]),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'ingredients' => Ingredient::where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit', 'cost_per_unit']),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->safe()->except('ingredients'));

        $this->syncIngredients($product, $request->ingredients ?? []);

        return redirect()->route('products.index');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return back();
    }

    public function restore(int $id): RedirectResponse
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return back();
    }

    /**
     * Sync ingredients pivot and auto-calculate cost from them.
     *
     * @param  array<int, array{ingredient_id: int, quantity: float}>  $ingredients
     */
    private function syncIngredients(Product $product, array $ingredients): void
    {
        $sync = collect($ingredients)->mapWithKeys(
            fn ($i) => [(int) $i['ingredient_id'] => ['quantity' => $i['quantity']]]
        )->all();

        $product->ingredients()->sync($sync);

        if (! empty($sync)) {
            $product->load('ingredients');
            $product->cost = $product->ingredientCost();
            $product->save();
        }
    }
}
