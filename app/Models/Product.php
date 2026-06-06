<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'stock',
        'min_stock',
        'created_by',
    ];

    protected $appends = ['current_stock'];

    public function stockMovements()
    {
        return $this->hasMany(\App\Models\StockMovement::class);
    }

    public function currentStock()
    {
        $in = $this->stockMovements()
            ->where('type', 'IN')
            ->sum('quantity');

        $out = $this->stockMovements()
            ->where('type', 'OUT')
            ->sum('quantity');

        return $in - $out;
    }

    public function getCurrentStockAttribute()
    {
        return $this->currentStock();
    }
}
