<?php

declare(strict_types=1);

use HosmelQ\AuditLog\Support\Config;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function down(): void
    {
        Schema::dropIfExists(Config::storageTable());
    }

    public function getConnection(): null|string
    {
        return Config::storageConnection();
    }

    public function up(): void
    {
        Schema::create(Config::storageTable(), function (Blueprint $table): void {
            $table->string('id')->primary();

            $table->string('actor_id')->index();
            $table->json('actor_metadata');
            $table->string('actor_name')->nullable();
            $table->string('actor_type')->index();
            $table->string('bucket')->default('application');
            $table->string('correlation_id')->nullable()->index();
            $table->text('description');
            $table->string('event')->index();
            $table->dateTime('expires_at', 3)->nullable()->index();
            $table->dateTime('inserted_at', 3)->index();
            $table->json('metadata');
            $table->dateTime('occurred_at', 3)->index();
            $table->string('remote_ip')->nullable();
            $table->string('source')->default('platform');
            $table->json('targets');
            $table->string('tenant_id');
            $table->text('user_agent')->nullable();

            $table->index(['tenant_id', 'bucket', 'occurred_at', 'id']);
        });
    }
};
