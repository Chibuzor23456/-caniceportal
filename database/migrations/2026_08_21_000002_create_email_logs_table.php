<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Populated directly inside PHPMailerTransport::doSend() (Section
        // 12) - the single chokepoint every outgoing email already passes
        // through, so no existing Mailable needs to change.
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient');
            $table->string('subject');
            $table->string('message_id')->nullable()->index();
            $table->string('status', 20)->default('sent');
            $table->text('error_message')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
