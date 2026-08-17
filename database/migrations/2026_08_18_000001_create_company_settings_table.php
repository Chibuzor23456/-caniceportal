<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Singleton row: the minimal Settings slice the Quotation module
        // needs (Section 10 - "Admin uploads a company signature once, in
        // Settings"). The full Settings module is a later phase.
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('signature_image_path')->nullable();
            $table->string('signatory_name')->nullable();
            $table->string('signatory_position')->nullable();
            $table->string('default_currency', 3)->default('NGN');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
