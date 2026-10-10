<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_prebooking')->default(false)->after('is_featured');
            // When the real product is expected to ship/launch — shown to customers.
            $table->date('expected_date')->nullable()->after('is_prebooking');
            // Optional admin note shown alongside the pre-booking badge,
            // e.g. "Ships within 3 days of launch".
            $table->string('prebooking_note', 255)->nullable()->after('expected_date');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_prebooking', 'expected_date', 'prebooking_note']);
        });
    }
};
