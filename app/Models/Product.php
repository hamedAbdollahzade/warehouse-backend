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

    // این باعث می‌شود current_stock در API خروجی هم نمایش داده شود
    protected $appends = ['current_stock', 'has_opening'];

    /*
    Relation

    هر محصول چندین stock movement دارد
    */
    public function stockMovements()
    {
        return $this->hasMany(\App\Models\StockMovement::class);
    }

    /*
    محاسبه موجودی فعلی

    موجودی = مجموع ورودها - مجموع خروج‌ها
    */
    public function currentStock()
    {

        /*
        OPENING و IN هر دو موجودی را زیاد می‌کنند
        */
        $in = $this->stockMovements()
            ->whereIn('type', ['OPENING', 'IN'])
            ->sum('quantity');

        /*
        OUT موجودی را کم می‌کند
        */
        $out = $this->stockMovements()
            ->where('type', 'OUT')
            ->sum('quantity');

        return $in - $out;
    }

    /*
    این accessor باعث می‌شود بتوانیم بنویسیم:

    $product->current_stock
    */
    public function getCurrentStockAttribute()
    {
        return $this->currentStock();
    }

    public function getHasOpeningAttribute()
    {
        return $this->stockMovements()
            ->where('type', 'OPENING')
            ->exists();
    }


}
