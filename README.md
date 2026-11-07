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

## Install

Requires `form-processor` and `action-provider`. The supported CiviCRM and PHP versions are in
`info.xml`. Download the zip from the
[releases page](https://github.com/civico-de/formprocessorperms/releases), unpack it into the
extensions directory and enable it. There is no configuration.

## Usage

- After creating or changing a form processor's permission, flush caches with `cv flush`
  (on Drupal also the Drupal cache) so the new string is registered.
- Uninstalling unregisters the strings again. Standalone then strips them from roles on
  the next role save.
- An API user additionally needs `authenticate with api key` (authx) and
  `access CiviCRM backend and API`. Those are separate from the per-processor permission.

## Development

The permission hook reads `civicrm_form_processor_instance` with direct SQL: it runs while the
permission list is being built, and an API call there would recurse into permission checking.
Its table check covers the window where `form-processor` is disabled underneath.

CI runs every civikitchen gate (`ck ci`) and this repository's E2E workflow on Standalone and the
CMS flavors. Locally, in the civikitchen image:

```bash
docker compose -f .docker/docker-compose.ci.yml up -d --wait
docker compose -f .docker/docker-compose.ci.yml exec -w /var/www/html/ext/formprocessorperms app ck ci
docker compose -f .docker/docker-compose.ci.yml exec -T app \
  bash -c "cd /var/www/html/ext/formprocessorperms && cv scr tests/e2e/e2e.php && tests/e2e/e2e-http-standalone.sh"
```

## License and support

AGPL-3.0-or-later. © civico GmbH. See [LICENSE.txt](LICENSE.txt). Report bugs in the
[issue tracker](https://github.com/civico-de/formprocessorperms/issues). Report security issues by
email to security@civico.de, not in a public issue; see [SECURITY.md](SECURITY.md).
