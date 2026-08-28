<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * In-app notification feed shared by the client portal and the staff/admin
 * panel. One row per recipient. `Notifier::push()` writes them; the AlertBell
 * Livewire component reads, badges and pops them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('icon')->default('fa-bell');
            $table->string('level', 20)->default('info'); // info | success | warning
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at']);
        });

        Schema::table('calendar_events', function (Blueprint $table) {
            $table->boolean('notify_all')->default(false)->after('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', fn (Blueprint $table) => $table->dropColumn('notify_all'));
        Schema::dropIfExists('user_alerts');
    }
};
