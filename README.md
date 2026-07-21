# formprocessorperms

Registers the per-processor permission strings of the
[Form Processor](https://lab.civicrm.org/extensions/form-processor) extension
via `hook_civicrm_permission`.

## Why

Form Processor lets each processor require a custom permission string and
enforces it on API calls (`hook_civicrm_alterAPIPermissions`), but never
registers those strings as permissions. Consequences:

- **Standalone:** `Role.permissions` is a pseudoconstant-backed field. APIv4
  silently drops any permission string that is not registered when a role is
  saved — the role looks correct in the UI, but the permission is never
  persisted, and every API call fails with "Authorization failed".
- **Drupal:** the string works if typed correctly, but is never advertised in
  the People → Permissions UI.

This extension reads the configured permission strings from
`civicrm_form_processor_instance` and registers each one, making them
assignable on Standalone and visible in the Drupal permissions UI.

## Usage

Install and enable alongside `form-processor`. No configuration.

- After creating or changing a form processor's permission, flush caches
  (`cv flush`; on Drupal also the Drupal cache) so the new string is
  registered.
- Uninstalling makes the strings unregistered again; Standalone will silently
  strip them from roles on the next role save.
- An API user additionally needs `authenticate with api key` (authx) and
  `access CiviCRM backend and API` — those are separate from the
  per-processor permission.
