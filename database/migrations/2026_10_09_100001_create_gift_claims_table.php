<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // One row per redeemed free gift. The UNIQUE indexes are the real
        // anti-abuse guarantee: even two simultaneous requests cannot both
        // insert, because the database rejects the second one.
        Schema::create('gift_claims', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('device_id', 128)->unique();
            $t->string('email')->unique();          // lower-cased
            $t->string('phone_key', 20)->unique();  // last 10 digits only
            $t->unsignedInteger('points');
            $t->string('ip', 45)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_claims');
    }
};
