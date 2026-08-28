<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('notifications_seen_at')->nullable()->after('remember_token');
            $table->dateTime('messages_seen_at')->nullable()->after('notifications_seen_at');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            // Time the tab was actually open + visible today (heartbeat-tracked).
            $table->unsignedInteger('active_minutes')->default(0)->after('worked_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['notifications_seen_at', 'messages_seen_at']));
        Schema::table('attendance_records', fn (Blueprint $t) => $t->dropColumn('active_minutes'));
    }
};
