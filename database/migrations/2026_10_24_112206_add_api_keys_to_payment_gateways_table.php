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
        Schema::table('payment_gateways', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_gateways', 'key_id')) {
                $table->string('key_id')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('payment_gateways', 'key_secret')) {
                $table->string('key_secret')->nullable()->after('key_id');
            }
            if (!Schema::hasColumn('payment_gateways', 'mode')) {
                $table->enum('mode', ['sandbox', 'live'])->default('sandbox')->after('key_secret');
            }
            if (!Schema::hasColumn('payment_gateways', 'webhook_secret')) {
                $table->string('webhook_secret')->nullable()->after('mode');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropColumn(['key_id', 'key_secret', 'mode', 'webhook_secret']);
        });
    }
};
