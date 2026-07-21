<?php
declare(strict_types = 1);

use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * Checks that permission strings configured on Form Processor instances are
 * registered via hook_civicrm_permission.
 *
 * Note: the Standalone-specific effect (unregistered permissions being
 * silently stripped from Role.permissions on save) cannot be asserted here —
 * the headless UF is 'UnitTests', not 'Standalone', so civicrm_role does not
 * exist. This test covers the registration half; the strip/persist behavior
 * was verified manually against a Standalone instance.
 *
 * @group headless
 */
class CRM_Formprocessorperms_PermissionTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->install(['action-provider', 'form-processor'])
      ->installMe(__DIR__)
      ->apply();
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
