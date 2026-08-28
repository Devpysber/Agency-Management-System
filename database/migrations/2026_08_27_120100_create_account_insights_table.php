<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('headline');
            $table->text('summary');
            $table->json('sections');          // [{title, body, sentiment}]
            $table->json('metrics')->nullable(); // the data snapshot the analysis was built from
            $table->string('model')->nullable();
            $table->string('input_digest', 64)->index(); // sha1 of the snapshot — lets us skip regen
            $table->string('generated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_insights');
    }
};
