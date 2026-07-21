<?php
/**
 * End-to-end check, run against a real installed site: `cv scr tests/e2e/e2e.php`
 *
 * Creates a form processor with a custom permission string and asserts that
 * formprocessorperms registers it. On Standalone additionally asserts the
 * original bug is fixed: the permission survives a Role save instead of being
 * silently stripped.
 */

$fail = function (string $msg): void {
  fwrite(STDERR, "E2E FAIL: $msg\n");
  exit(1);
};

$perm = 'e2e fp perm';

$existing = civicrm_api3('FormProcessorInstance', 'get', ['name' => 'e2e_fp']);
if (empty($existing['count'])) {
  civicrm_api3('FormProcessorInstance', 'create', [
    'name' => 'e2e_fp',
    'title' => 'E2E FP',
    'permission' => $perm,
    'is_active' => 1,
    'output_handler' => 'OutputAllActionOutput',
  ]);
}

civicrm_api3('System', 'flush', []);
// In-process: make sure neither our hook cache nor core's permission cache
// serves a pre-create snapshot.
unset(\Civi::$statics['formprocessorperms']);
unset(\Civi::$statics['CRM_Core_Permission']);
\Civi::cache('metadata')->clear();

$permissions = CRM_Core_Permission::basicPermissions(TRUE);
if (!isset($permissions[$perm])) {
  $fail("'$perm' not in CRM_Core_Permission::basicPermissions() — registration broken");
}
echo "registered: '$perm'\n";

// Enforcement: form_processor's alterAPIPermissions gate must reject a caller
// without the permission and accept one with it. Simulated in-process via a
// fake permission class; the real HTTP/authx path is covered separately on
// Standalone (tests/e2e/e2e-http-standalone.sh).
$config = CRM_Core_Config::singleton();
$origPermClass = $config->userPermissionClass;
$fake = new CRM_Core_Permission_UnitTests();
$config->userPermissionClass = $fake;
try {
  $fake->permissions = ['access CiviCRM'];
  $denied = FALSE;
  try {
    civicrm_api3('FormProcessor', 'e2e_fp', ['check_permissions' => 1]);
  }
  catch (\Throwable $e) {
    if (stripos($e->getMessage(), 'authoriz') !== FALSE || stripos($e->getMessage(), 'permission') !== FALSE) {
      $denied = TRUE;
    }
    else {
      $fail('call without permission failed for an unexpected reason: ' . $e->getMessage());
    }
  }
  if (!$denied) {
    $fail('API call WITHOUT the permission was not rejected');
  }
  echo "enforcement: call without '$perm' rejected\n";

  $fake->permissions = ['access CiviCRM', $perm];
  try {
    civicrm_api3('FormProcessor', 'e2e_fp', ['check_permissions' => 1]);
  }
  catch (\Throwable $e) {
    if (stripos($e->getMessage(), 'authoriz') !== FALSE) {
      $fail('API call WITH the permission was still rejected: ' . $e->getMessage());
    }
    // Any non-authorization error (e.g. the empty processor has no actions)
    // means the permission gate itself passed — good enough here.
  }
  echo "enforcement: call with '$perm' passed the permission gate\n";
}
finally {
  $config->userPermissionClass = $origPermClass;
}

if (CIVICRM_UF === 'Standalone') {
  $role = \Civi\Api4\Role::get(FALSE)
    ->addWhere('name', '=', 'staff')
    ->execute()->first();
  if (!$role) {
    $fail('no staff role found on Standalone');
  }
  $perms = $role['permissions'];
  if (!in_array($perm, $perms, TRUE)) {
    $perms[] = $perm;
  }
  \Civi\Api4\Role::update(FALSE)
    ->addWhere('id', '=', $role['id'])
    ->addValue('permissions', $perms)
    ->execute();
  $db = CRM_Core_DAO::singleValueQuery(
    'SELECT permissions FROM civicrm_role WHERE id = %1',
    [1 => [$role['id'], 'Integer']]
  );
  if (strpos((string) $db, $perm) === FALSE) {
    $fail("'$perm' was stripped from civicrm_role on save — Standalone fix broken");
  }
  echo "role persistence: '$perm' survived Role save\n";
}

echo 'E2E PASS (' . CIVICRM_UF . ")\n";
