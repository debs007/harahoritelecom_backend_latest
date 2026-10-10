<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_in_prices', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('model');
            // Current Indian market price for a NEW unit of this model —
            // the reference the exchange value gets capped against, so a
            // cheap old phone can't be used to extract a disproportionate
            // exchange credit off an unrelated, expensive product purchase.
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['brand', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_in_prices');
    }
};
