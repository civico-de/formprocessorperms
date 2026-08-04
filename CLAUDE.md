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
  and the table check for the form_processor-absent window; the try/catch
  behind it logs at error level, because there a query really did fail.
- The extension stays configuration-free and dependency-light; behavior
  changes belong in the hook, not in new settings.
- Remember the failure mode when reasoning about tests: uninstalling (or a
  stale cache after a permission change) re-triggers the silent strip on the
  next role save.

## Verify

CI is the shared civikitchen pipeline (`.github/workflows/ci.yml` → cklint,
ckconform, ckfmt, phpunit + coverage, phpstan) plus this repo's own E2E
workflow (Standalone + CMS matrix). The same paths work locally:

```bash
docker compose -f .docker/docker-compose.ci.yml up -d
docker compose -f .docker/docker-compose.ci.yml exec -T -e CIVICRM_UF=UnitTests app \
  bash -c "cd /var/www/html/ext/formprocessorperms && ckcoverage && cklint --all && ckconform && phpstan analyse"
docker compose -f .docker/docker-compose.ci.yml exec -T app \
  bash -c "cd /var/www/html/ext/formprocessorperms && cv scr tests/e2e/e2e.php && tests/e2e/e2e-http-standalone.sh"
```

Template-managed files come from civikitchen (`.ckconform` records the two
deviations); refresh with `tools/ckinit.php --update .` from a checkout.
