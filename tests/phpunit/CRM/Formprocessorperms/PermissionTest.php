<?php

declare(strict_types = 1);

use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * Registration half only: the Standalone strip-on-save effect needs
 * civicrm_role, which the 'UnitTests' UF lacks — tests/e2e/e2e.php covers it.
 * civicrm_api3() is deliberate: FormProcessorInstance is APIv3-only.
 *
 * @group headless
 */
class CRM_Formprocessorperms_PermissionTest extends TestCase implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless(): CiviEnvBuilder {
    return ck_headless()->apply();
  }

  public function testProcessorPermissionIsRegistered(): void {
    civicrm_api3('FormProcessorInstance', 'create', [
      'name' => 'test_fp',
      'title' => 'Test FP',
      'permission' => 'test fp perm',
      'is_active' => 1,
      'output_handler' => 'OutputAllActionOutput',
    ]);
    // The hook caches per-process and core caches the permission list.
    unset(\Civi::$statics['formprocessorperms']);
    \Civi::cache('metadata')->clear();

    $permissions = \CRM_Core_Permission::basicPermissions(TRUE);
    $this->assertArrayHasKey('test fp perm', $permissions);
  }

  public function testProcessorWithoutPermissionRegistersNothing(): void {
    civicrm_api3('FormProcessorInstance', 'create', [
      'name' => 'test_fp_open',
      'title' => 'Open FP',
      'permission' => '',
      'is_active' => 1,
      'output_handler' => 'OutputAllActionOutput',
    ]);
    unset(\Civi::$statics['formprocessorperms']);
    \Civi::cache('metadata')->clear();

    $permissions = \CRM_Core_Permission::basicPermissions(TRUE);
    $this->assertArrayNotHasKey('', $permissions);
  }

}
