<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
   {
       Schema::create('products', function (Blueprint $table) {
           $table->id();

           $table->string('sku')->unique();          // کد محصول (یکتا)
           $table->string('name');                   // نام محصول
           $table->text('description')->nullable();  // توضیحات

           $table->unsignedInteger('stock')->default(0); // موجودی فعلی (فعلاً ساده)
           $table->unsignedInteger('min_stock')->default(0); // حداقل موجودی برای هشدار

           $table->unsignedBigInteger('created_by')->nullable(); // کاربر سازنده (اختیاری فاز اول)

           $table->timestamps();

           $table->index(['name']);
       });
   }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
