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
            'type' => 'required|in:OPENING,IN,OUT,ADJUST',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string'
        ]);

        $product = Product::findOrFail($data['product_id']);

        /*
        =========================================
        جلوگیری از ثبت چند Opening
        =========================================
        */
        if ($data['type'] === 'OPENING') {

            $exists = StockMovement::where('product_id', $data['product_id'])
                ->where('type', 'OPENING')
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Opening balance already exists'
                ], 422);
            }
        }

        /*
        =========================================
        منطق ADJUST (موجودی واقعی = X)
        =========================================
        */
        if ($data['type'] === 'ADJUST') {

            $current = $product->current_stock;

            $difference = $data['quantity'] - $current;

            if ($difference == 0) {
                return response()->json([
                    'message' => 'تغییری در موجودی ایجاد نشد'
                ], 422);
            }

            // اگر موجودی واقعی بیشتر بود → IN
            if ($difference > 0) {
                $data['type'] = 'IN';
                $data['quantity'] = $difference;
            } else {
                // اگر کمتر بود → OUT
                $data['type'] = 'OUT';
                $data['quantity'] = abs($difference);
            }
        }

        /*
        =========================================
        جلوگیری از موجودی منفی در OUT
        =========================================
        */
        if ($data['type'] === 'OUT') {

            if ($product->current_stock < $data['quantity']) {
                return response()->json([
                    'message' => 'موجودی کافی نیست'
                ], 422);
            }
        }

        /*
        =========================================
        ثبت movement
        =========================================
        */
        $movement = StockMovement::create([
            'product_id' => $data['product_id'],
            'type' => $data['type'],
            'quantity' => $data['quantity'],
            'note' => $data['note'] ?? null,
        ]);

        return response()->json($movement, 201);
    }

    /*
    ====================================================
    لیست کاردکس محصول (با Running Balance واقعی)
    ====================================================
    */
    public function productMovements($productId)
    {
        $movements = StockMovement::where('product_id', $productId)
            ->orderBy('created_at')
            ->get();

        $balance = 0;

        $result = $movements->map(function ($m) use (&$balance) {

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

        return response()->json($result);
    }
}
