<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0', 'gte:price_min'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $products = Product::query()
            ->with('categories:id,name')
            ->when(isset($validated['name']), function ($query) use ($validated) {
                $query->where('name', 'like', '%' . $validated['name'] . '%');
            })
            ->when(isset($validated['category_id']), function ($query) use ($validated) {
                $query->whereHas('categories', function ($q) use ($validated) {
                    $q->where('categories.id', $validated['category_id']);
                });
            })
            ->when(isset($validated['price_min']), function ($query) use ($validated) {
                $query->where('price', '>=', $validated['price_min']);
            })
            ->when(isset($validated['price_max']), function ($query) use ($validated) {
                $query->where('price', '<=', $validated['price_max']);
            })
            ->orderBy('name')
            ->paginate($validated['per_page'] ?? 10);

        return response()->json($products);
    }

    public function show(Product $product)
    {
        return response()->json(
            $product->load('categories:id,name')
        );
    }
}
