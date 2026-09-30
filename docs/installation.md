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

Run the installer to publish the configuration and migrations:

```bash
php artisan audit-log:install
```

The installer can run the migrations for you. If you skip that prompt, run them manually:

```bash
php artisan migrate
```

## Publish manually

To publish each asset without the installer, run:

```bash
php artisan vendor:publish --tag="audit-log-config"
php artisan vendor:publish --tag="audit-log-migrations"
```

Continue with [configuration](configuration), then [record your first audit log](basic-usage/recording-audit-logs).

## Update existing installations

After updating the package, publish any new migrations and run them:

```bash
php artisan vendor:publish --tag="audit-log-migrations"
php artisan migrate
```

The attribute changes migration adds a nullable `changes` column to the configured audit log table. Existing logs are preserved and have a null value. Run this migration before recording logs with the updated package.
