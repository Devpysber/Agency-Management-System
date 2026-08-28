<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Calendar events can now carry a project + a client contact + an explicit
 * meeting link, so the app can work out who a meeting/call is actually between
 * (staff <-> client, staff <-> admin) and notify exactly those people with the
 * full details and a link. `notified_digest` makes re-notification idempotent;
 * `communication_id` ties the event to the activity-log row it keeps in sync.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('assigned_to')
                ->constrained('projects')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->after('project_id')
                ->constrained('contacts')->nullOnDelete();
            $table->string('meeting_url')->nullable()->after('location');
            $table->string('notified_digest')->nullable()->after('meeting_url');
            $table->foreignId('communication_id')->nullable()->after('notified_digest')
                ->constrained('communications')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
            $table->dropConstrainedForeignId('contact_id');
            $table->dropConstrainedForeignId('communication_id');
            $table->dropColumn(['meeting_url', 'notified_digest']);
        });
    }
};
