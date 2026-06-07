<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {

            // اضافه شدن OPENING به enum
            $table->enum('type', [
                'OPENING',
                'IN',
                'OUT',
                'ADJUST'
            ])->change();

        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {

            // بازگشت به حالت قبلی
            $table->enum('type', [
                'IN',
                'OUT',
                'ADJUST'
            ])->change();

        });
    }
};
