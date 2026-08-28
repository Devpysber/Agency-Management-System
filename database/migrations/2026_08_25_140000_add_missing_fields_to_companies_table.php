<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('status')->default('active')->after('social_media');
            $table->string('company_type')->nullable()->after('status');
            $table->string('company_employee_count')->nullable()->after('company_type');
            $table->text('company_description')->nullable()->after('company_employee_count');
            $table->string('company_postal_code')->nullable()->after('company_description');
            $table->text('company_social')->nullable()->after('company_postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'company_type',
                'company_employee_count',
                'company_description',
                'company_postal_code',
                'company_social',
            ]);
        });
    }
};
