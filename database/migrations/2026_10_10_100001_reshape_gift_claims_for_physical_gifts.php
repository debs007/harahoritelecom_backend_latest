<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('gift_claims', function (Blueprint $t) {
            if (Schema::hasColumn('gift_claims', 'points')) {
                $t->dropColumn('points');
            }
            $t->string('gift_name')->nullable()->after('phone_key');
            $t->string('status', 20)->default('pending')->index()->after('gift_name');
            // Address is copied (snapshotted) at claim time so later edits or
            // deletion of the saved address never change where the gift ships.
            $t->string('ship_name')->nullable();
            $t->string('ship_phone', 30)->nullable();
            $t->string('ship_address1')->nullable();
            $t->string('ship_address2')->nullable();
            $t->string('ship_city')->nullable();
            $t->string('ship_state')->nullable();
            $t->string('ship_pincode', 12)->nullable();
            $t->string('ship_country')->nullable();
            $t->timestamp('confirmed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('gift_claims', function (Blueprint $t) {
            $t->dropColumn([
                'gift_name', 'status', 'ship_name', 'ship_phone', 'ship_address1',
                'ship_address2', 'ship_city', 'ship_state', 'ship_pincode',
                'ship_country', 'confirmed_at',
            ]);
            $t->unsignedInteger('points')->default(0);
        });
    }
};
