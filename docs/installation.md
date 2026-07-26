---
title: "Installation"
description: "Install Laravel Audit Logs and publish its configuration and migration."
weight: 2
---

## Requirements

Laravel Audit Logs requires PHP 8.3 or later and Laravel 12 or 13.

## Install the package

Install the package with Composer:

```bash
composer require hosmelq/laravel-audit-logs
```

Run the installer to publish the configuration and migration:

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
