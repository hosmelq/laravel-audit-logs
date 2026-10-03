# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## v2.0.0 - 2026-10-04

- Require PHP 8.4 or newer.
- Update the Pint, Rector, PHPStan, and Pest configuration.
- Rework the GitHub Actions workflows and add GitHub Actions security analysis.
- Add context resolvers for default actors and tenants.
- Add redaction rules that exclude or mask sensitive metadata.
- Add `withoutRequestMetadata()` to omit the remote IP and user agent.
- Change the parameter order of the `AuditLogData` constructor.
- Fill a missing remote IP and user agent on prepared logs in `AuditLog::fake()`, as database storage does.
- Add `changes()` to record attribute changes.
- Add a migration for the new `attribute_changes` column. Run it before recording logs.
- Preserve float values in event, actor, and target metadata when storing logs.

See [UPGRADE.md](UPGRADE.md) for upgrade instructions.

## [v1.1.0](https://github.com/hosmelq/laravel-audit-logs/compare/v1.0.0...v1.1.0) - 2026-06-25

### Added

- Added audit log identities for passing objects directly as actors and targets.

### Removed

- Removed the redundant audit logs `tenant_id` index.

## v1.0.0 - 2026-06-11

Initial release.
