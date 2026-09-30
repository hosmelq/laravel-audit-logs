<?php

declare(strict_types=1);

use HosmelQ\AuditLog\Support\Config;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function down(): void
    {
        Schema::table(Config::storageTable(), function (Blueprint $table): void {
            $table->dropColumn('attribute_changes');
        });
    }

    public function getConnection(): null|string
    {
        return Config::storageConnection();
    }

    public function up(): void
    {
        Schema::table(Config::storageTable(), function (Blueprint $table): void {
            $table->json('attribute_changes')->after('bucket')->nullable();
        });
    }
};
