<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staff <-> CEO direct chat. The CEO side is resolved dynamically by
 * designation (staff.designation === 'CEO'), never a hardcoded user id —
 * matches the whole session's stance against hardcoded designation/ID
 * checks. One thread per staff member (identified by the pair of
 * participants), not per-message routing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direct_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('to_user_id')->constrained('users')->onDelete('cascade');
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['from_user_id', 'to_user_id']);
            $table->index(['to_user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_messages');
    }
};
