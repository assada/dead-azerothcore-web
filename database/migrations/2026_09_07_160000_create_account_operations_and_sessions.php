<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'acore_auth';

    public function up(): void
    {
        Schema::connection($this->connection)->create('account_profiles', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->primary();
            $table->string('verified_email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('pending_email')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->unsignedInteger('deactivation_ban_date')->nullable();
        });

        Schema::connection($this->connection)->create('account_operations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('realm_id')->nullable();
            $table->unsignedInteger('character_guid')->nullable();
            $table->string('character_name', 48)->nullable();
            $table->string('action', 24);
            $table->string('status', 16)->default('completed');
            $table->json('context')->nullable();
            $table->timestamps();
            $table->index(['account_id', 'created_at']);
            $table->index(['realm_id', 'character_guid']);
        });

        Schema::connection($this->connection)->create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('sessions');
        Schema::connection($this->connection)->dropIfExists('account_operations');
        Schema::connection($this->connection)->dropIfExists('account_profiles');
    }
};
