<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog;

use HosmelQ\AuditLog\Commands\CommitAuditLogs;
use HosmelQ\AuditLog\Contracts\AuditLogManager;
use HosmelQ\AuditLog\Contracts\AuditLogRepository;
use HosmelQ\AuditLog\Repositories\ArrayAuditLogRepository;
use HosmelQ\AuditLog\Repositories\RedisAuditLogRepository;
use HosmelQ\AuditLog\Support\Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Queue;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AuditLogServiceProvider extends PackageServiceProvider
{
    /**
     * Configure package.
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('audit-log')
            ->hasCommand(CommitAuditLogs::class)
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

    /**
     * Bootstrap bindings.
     */
    public function packageBooted(): void
    {
        if (! Config::autoCommit()) {
            return;
        }

        $this->app->terminating(function (): void {
            $this->commitPendingAuditLogs();
        });

        Queue::after(function (): void {
            $this->commitPendingAuditLogs();
        });

        Queue::exceptionOccurred(function (): void {
            if (Config::queueCommitAfterFailure()) {
                $this->commitPendingAuditLogs();

                return;
            }

            rescue(function (): void {
                $this->app->make(AuditLogRepository::class)->flush();
            });
        });
    }

    /**
     * Register bindings.
     */
    public function packageRegistered(): void
    {
        $this->app->scoped(AuditLogManager::class, DatabaseAuditLogManager::class);
        $this->app->scoped(AuditLogRepository::class, function (Application $app): AuditLogRepository {
            return match (Config::driver()) {
                'redis' => $app->make(RedisAuditLogRepository::class),
                default => $app->make(ArrayAuditLogRepository::class),
            };
        });
    }

    private function commitPendingAuditLogs(): void
    {
        rescue(function (): void {
            $this->app->make(AuditLogManager::class)->commit();
        });
    }
}
