<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // چرا؟ برای لیست محصولات + قابلیت سرچ ساده
        $query = Product::query();

        if ($search = $request->query('q')) {
            $query->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                   ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // چرا pagination؟ برای اینکه بعداً با دیتای زیاد کند نشه
        return response()->json(
            $query->orderByDesc('id')->paginate(10)
        );
    }

    public function store(Request $request)
    {
        // چرا validate؟ تا داده خراب وارد DB نشه و پیام خطای تمیز به فرانت بدیم
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
        ]);

        $product = Product::create([
            ...$validated,
            'stock' => $validated['stock'] ?? 0,
            'min_stock' => $validated['min_stock'] ?? 0,
            'created_by' => $request->user()->id, // چرا؟ ثبت سازنده محصول
        ]);

        return response()->json([
            'message' => 'Product created successfully.',
            'data' => $product,
        ], 201);
    }

    public function show(Product $product)
    {
        // چرا route-model binding؟ ساده‌سازی دریافت محصول از DB با id
        return response()->json([
            'data' => $product,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($product->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
        ]);

        $product->update([
            ...$validated,
            'stock' => $validated['stock'] ?? $product->stock,
            'min_stock' => $validated['min_stock'] ?? $product->min_stock,
        ]);

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => $product->fresh(),
        ]);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }
}
