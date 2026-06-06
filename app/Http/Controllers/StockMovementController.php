<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:IN,OUT,ADJUST',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string'
        ]);

    $product = Product::findOrFail($request->product_id);

    if ($request->type === 'OUT') {
        if ($product->current_stock < $request->quantity) {
            return response()->json([
                'message' => 'موجودی کافی نیست'
            ], 422);
        }
    }

    if ($request->type === 'ADJUST') {

        $current = $product->current_stock;
        $difference = $request->quantity - $current;

        if ($difference == 0) {
            return response()->json([
                'message' => 'تغییری در موجودی ایجاد نشد'
            ], 422);
        }

        $request->merge([
            'quantity' => abs($difference),
            'type' => $difference > 0 ? 'IN' : 'OUT'
        ]);
    }

    $movement = StockMovement::create($request->all());

    return response()->json($movement);
    }

    public function productMovements($productId)
    {
        $movements = StockMovement::where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($movements);
    }

    public function adjust(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer',
            'note' => 'nullable|string'
        ]);

        $movement = StockMovement::create([
            'product_id' => $request->product_id,
            'type' => 'ADJUST',
            'quantity' => $request->quantity,
            'note' => $request->note,
        ]);

        return response()->json($movement, 201);
    }


}
