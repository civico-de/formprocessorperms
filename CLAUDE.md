# formprocessorperms

Registers Form Processor's per-processor permission strings via
`hook_civicrm_permission`. Reason for existing: Form Processor enforces those
strings but never registers them, and CiviCRM Standalone silently strips
unregistered permission strings from roles on save — the role looks right in
the UI while every API call fails with "Authorization failed". On Drupal the
strings merely never appear in the permissions UI.

## Work in this repository

- The permission hook must read `civicrm_form_processor_instance` via direct
  SQL: it runs while the permission list is being built, and an API call
  there recurses into permission checking. Keep the `Civi::$statics` cache
  and the try/catch for the form_processor-absent window.
- The extension stays configuration-free and dependency-light; behavior
  changes belong in the hook, not in new settings.
- Remember the failure mode when reasoning about tests: uninstalling (or a
  stale cache after a permission change) re-triggers the silent strip on the
  next role save.

## Verify

CI runs a Standalone job plus a CMS matrix; the same paths work locally:

```bash
docker compose -f docker-compose.ci.yml up -d
docker compose -f docker-compose.ci.yml exec -T app cv ext:enable formprocessorperms
docker compose -f docker-compose.ci.yml exec -T -e CIVICRM_UF=UnitTests app \
  bash -c "cd /var/www/html/ext/formprocessorperms && phpunit"
docker compose -f docker-compose.ci.yml exec -T app \
  bash -c "cd /var/www/html/ext/formprocessorperms && cv scr tests/e2e/e2e.php && tests/e2e/e2e-http-standalone.sh"
```
