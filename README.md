# formprocessorperms

Registers the per-processor permission strings of the
[Form Processor](https://lab.civicrm.org/extensions/form-processor) extension through
`hook_civicrm_permission`.

## Why

Form Processor lets each processor require a custom permission string and enforces it on
API calls through `hook_civicrm_alterAPIPermissions`. It never registers those strings as
permissions. That has two consequences.

On Standalone, `Role.permissions` is a pseudoconstant-backed field. When a role is saved,
APIv4 silently drops any permission string that is not registered. The role looks correct
in the UI, but the permission is never persisted, and every API call fails with
"Authorization failed".

On Drupal, the string works if typed correctly, but it is never advertised in the
People → Permissions UI.

This extension reads the configured permission strings from
`civicrm_form_processor_instance` and registers each one. That makes them assignable on
Standalone and visible in the Drupal permissions UI.

## Usage

Install and enable it alongside `form-processor`. There is no configuration.

- After creating or changing a form processor's permission, flush caches with `cv flush`
  (on Drupal also the Drupal cache) so the new string is registered.
- Uninstalling unregisters the strings again. Standalone then strips them from roles on
  the next role save.
- An API user additionally needs `authenticate with api key` (authx) and
  `access CiviCRM backend and API`. Those are separate from the per-processor permission.
