<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            $table->text('notes')->nullable()->after('description');
            $table->date('expected_delivery_date')->nullable()->after('status');
            $table->date('completion_date')->nullable()->after('expected_delivery_date');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['description', 'notes', 'expected_delivery_date', 'completion_date']);
        });
    }
};
