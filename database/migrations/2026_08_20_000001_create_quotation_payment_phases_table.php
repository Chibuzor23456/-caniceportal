<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Structured phased-payment data (Section 14: "amount, description,
        // and due condition all pulled from what the client already signed")
        // for the builder's Payment Schedule section, alongside the freeform
        // quotation_sections row for that same section type.
        Schema::create('quotation_payment_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('due_condition')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create('quotation_template_payment_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_template_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('due_condition')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_template_payment_phases');
        Schema::dropIfExists('quotation_payment_phases');
    }
};
