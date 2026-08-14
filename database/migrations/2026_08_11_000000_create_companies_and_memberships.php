<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active')->index();
            $table->string('segment')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('whatsapp', 32)->nullable();
            $table->string('postal_code', 16)->nullable();
            $table->string('address')->nullable();
            $table->string('address_number', 32)->nullable();
            $table->string('address_complement')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('timezone')->default('America/Sao_Paulo');
            $table->unsignedSmallInteger('appointment_interval_minutes')->default(30);
            $table->unsignedSmallInteger('minimum_notice_minutes')->default(120);
            $table->unsignedSmallInteger('maximum_notice_days')->default(60);
            $table->json('onboarding_steps')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('professional')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 32)->nullable()->after('email');
            $table->foreignId('current_company_id')
                ->nullable()
                ->after('phone')
                ->constrained('companies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_company_id');
            $table->dropColumn('phone');
        });

        Schema::dropIfExists('company_user');
        Schema::dropIfExists('companies');
    }
};
