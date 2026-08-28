<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('aadhar', 20)->nullable()->after('whatsapp');
            $table->string('pan', 15)->nullable()->after('aadhar');
            $table->string('employment_type', 20)->default('full_time')->after('designation'); // full_time | intern | contract
            $table->date('tenure_start')->nullable()->after('joining_date');
            $table->date('tenure_end')->nullable()->after('tenure_start');
        });
    }

    public function down(): void
    {
        Schema::table('staff', fn (Blueprint $table) => $table->dropColumn(
            ['aadhar', 'pan', 'employment_type', 'tenure_start', 'tenure_end']
        ));
    }
};
