<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('quotation_template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_template_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->longText('body')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create('quotation_template_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_template_id')->constrained()->cascadeOnDelete();
            $table->string('service_name');
            $table->string('service_category')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_template_line_items');
        Schema::dropIfExists('quotation_template_sections');
        Schema::dropIfExists('quotation_templates');
    }
};
