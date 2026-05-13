<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Config::string('audit-log.database.logs_table'));
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(Config::string('audit-log.database.logs_table'), function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('actor_id')->index();
            $table->string('actor_name')->nullable();
            $table->json('actor_properties');
            $table->string('actor_type')->index();
            $table->string('batch_id')->nullable()->index();
            $table->string('bucket')->default('audit_logs');
            $table->text('description');
            $table->string('event')->index();
            $table->dateTime('expires_at', 3)->nullable()->index();
            $table->dateTime('inserted_at', 3)->index();
            $table->dateTime('occurred_at', 3)->index();
            $table->json('properties');
            $table->string('remote_ip')->nullable();
            $table->string('source')->default('platform');
            $table->json('targets');
            $table->string('tenant_id')->index();
            $table->text('user_agent')->nullable();

            $table->index(['tenant_id', 'bucket', 'occurred_at', 'id']);
        });
    }
};
