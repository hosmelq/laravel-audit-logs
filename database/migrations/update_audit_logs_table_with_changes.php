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
            $table->dropColumn('changes');
        });
    }

    public function getConnection(): null|string
    {
        return Config::storageConnection();
    }

    public function up(): void
    {
        Schema::table(Config::storageTable(), function (Blueprint $table): void {
            $table->json('changes')->after('bucket')->nullable();
        });
    }
};
