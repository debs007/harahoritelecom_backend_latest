<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exchange_requests', function (Blueprint $table) {
            // Null when the customer picked "Other — not listed"; those
            // requests get no instant discount and go to manual review.
            $table->foreignId('trade_in_price_id')->nullable()
                ->after('product_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exchange_requests', function (Blueprint $table) {
            $table->dropForeign(['trade_in_price_id']);
            $table->dropColumn('trade_in_price_id');
        });
    }
};
