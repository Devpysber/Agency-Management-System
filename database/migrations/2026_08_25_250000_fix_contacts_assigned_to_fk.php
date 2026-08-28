<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * contacts.assigned_to was constrained to `users`, but every other
     * assignable module (tasks, calendar_events, communications) — and the
     * Contact::assignedTo() relation / "Assigned To" UI — treats it as a
     * `staff` id. Repoint the foreign key so it actually matches.
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->foreign('assigned_to')->references('id')->on('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
        });
    }
};
