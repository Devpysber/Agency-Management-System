<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deals had no owner beyond `created_by` (the User who entered the row) —
 * no way to represent "Sales Executive owns this opportunity" or "BDM
 * assigned this lead to X". Adds the missing ownership column, same
 * pattern as tasks.assigned_to.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('company_id')
                ->constrained('staff')->onDelete('set null');
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
        });
    }
};
