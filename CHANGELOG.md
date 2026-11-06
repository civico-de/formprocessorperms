# Changelog

## [Unreleased]

## [1.0.1] - 2026-10-30

### Added

- Registers every permission string configured on a Form Processor instance through
  `hook_civicrm_permission`, so it can be assigned to Standalone roles and appears in the Drupal
  permissions UI.
- Declares `form-processor` and `action-provider` as required extensions.
- Supports PHP 8.2 and later.

### Fixed

- A failing query for the configured permission strings is reported instead of being silently
  ignored.

[Unreleased]: https://github.com/civico-de/formprocessorperms/compare/v1.0.1...HEAD
[1.0.1]: https://github.com/civico-de/formprocessorperms/releases/tag/v1.0.1
