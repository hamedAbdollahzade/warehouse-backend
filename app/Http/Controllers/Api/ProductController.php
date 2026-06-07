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

    public function lowStock()
    {
        $products = Product::all()->filter(function ($product) {
            return $product->current_stock <= $product->min_stock;
        })->values();

        return response()->json($products);
    }

    public function summary()
    {
        $products = Product::all();

        $totalProducts = $products->count();

        $totalStock = $products->sum(function ($product) {
            return $product->current_stock;
        });

        $lowStock = $products->filter(function ($product) {
            return $product->current_stock <= $product->min_stock && $product->current_stock > 0;
        })->count();

        $outOfStock = $products->filter(function ($product) {
            return $product->current_stock == 0;
        })->count();

        return response()->json([
            'total_products' => $totalProducts,
            'total_stock' => $totalStock,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
        ]);
    }



  public function kardex(Product $product)
  {
      $movements = $product->stockMovements()
          ->orderBy('created_at')
          ->get();

      $balance = 0;

      $kardex = $movements->map(function ($m) use (&$balance) {

          $change = match ($m->type) {
              'OPENING', 'IN' => $m->quantity,
              'OUT' => -$m->quantity,
              default => 0
          };

          $balance += $change;

          return [
              'id' => $m->id,
              'date' => $m->created_at,
              'type' => $m->type,
              'quantity' => $m->quantity,
              'change' => $change,
              'balance' => $balance,
              'note' => $m->note,
          ];
      });

      return response()->json($kardex);
  }




}
