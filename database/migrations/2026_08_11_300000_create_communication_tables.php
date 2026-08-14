<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->text('confirmation_token_encrypted')->nullable()->after('confirmation_token');
        });

        Schema::create('reminder_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('confirmation_enabled')->default(true);
            $table->unsignedInteger('confirmation_minutes_before')->default(1440);
            $table->boolean('reminders_enabled')->default(true);
            $table->json('reminder_offsets')->nullable();
            $table->json('channels')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->string('type', 60);
            $table->string('channel', 30);
            $table->string('status', 30)->default('pending');
            $table->string('idempotency_key', 191)->unique();
            $table->string('recipient')->nullable();
            $table->text('message');
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedSmallInteger('attempt')->default(0);
            $table->text('provider_message_id')->nullable();
            $table->text('error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status', 'scheduled_for']);
            $table->index(['company_id', 'appointment_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('reminder_settings');

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn('confirmation_token_encrypted');
        });
    }
};
