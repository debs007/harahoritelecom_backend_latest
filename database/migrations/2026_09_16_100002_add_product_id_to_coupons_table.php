<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            // Null = a normal site-wide coupon (existing behaviour).
            // Set  = only valid when this specific product is in the cart,
            //        and the discount only applies against that product's
            //        line amount, not the whole cart.
            $table->foreignId('product_id')->nullable()->after('id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }
};
