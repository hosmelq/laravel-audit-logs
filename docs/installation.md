---
title: "Installation"
description: "Install Laravel Audit Logs and publish its configuration and migrations."
weight: 2
---

## Requirements

Laravel Audit Logs requires PHP 8.4 or later and Laravel 12 or 13.

## Install the package

Install the package with Composer:

```bash
composer require hosmelq/laravel-audit-logs
```

Run the installer to publish the configuration file and migrations:

```bash
php artisan audit-log:install
```

The installer offers to run the migrations. If you skip that step, run them yourself:

```bash
php artisan migrate
```

The package publishes two migrations. The first creates the audit log table, and the second adds the `attribute_changes` column used for [attribute changes](basic-usage/recording-changes). Run both.

## Publish manually

To publish the files without the installer, run:

```bash
php artisan vendor:publish --tag="audit-log-config"
php artisan vendor:publish --tag="audit-log-migrations"
php artisan migrate
```

The migrations read `audit-log.storage.connection` and `audit-log.storage.table`. Set those options before migrating if logs should not use the default connection or the `audit_logs` table. See [configuration](configuration#storage).

## Next steps

Review the [configuration](configuration), then [record your first audit log](basic-usage/recording-audit-logs).
