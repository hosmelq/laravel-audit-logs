<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog;

use HosmelQ\AuditLog\Contracts\AuditLogManager;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class AuditLogServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('audit-log')
            ->hasConfigFile()
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('hosmelq/laravel-audit-logs')
                    ->publishConfigFile()
                    ->publishMigrations();
            })
            ->hasMigration('create_audit_logs_table');
    }

    public function packageRegistered(): void
    {
        $this->app->scoped(AuditLogCorrelation::class);
        $this->app->scoped(AuditLogId::class);
        $this->app->scoped(AuditLogManager::class, DatabaseAuditLogManager::class);
    }
}
