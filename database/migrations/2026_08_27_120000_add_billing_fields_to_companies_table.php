<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('legal_entity_name')->nullable()->after('company_name');
            $table->string('gstin', 20)->nullable()->after('company_registration_no');
            $table->string('pan', 15)->nullable()->after('gstin');
            $table->string('tax_registration_number', 60)->nullable()->after('pan');

            $table->string('billing_address')->nullable()->after('company_country');
            $table->string('billing_city', 120)->nullable()->after('billing_address');
            $table->string('billing_state', 120)->nullable()->after('billing_city');
            $table->string('billing_zip', 30)->nullable()->after('billing_state');
            $table->string('billing_country', 120)->nullable()->after('billing_zip');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'legal_entity_name', 'gstin', 'pan', 'tax_registration_number',
                'billing_address', 'billing_city', 'billing_state', 'billing_zip', 'billing_country',
            ]);
        });
    }
};
