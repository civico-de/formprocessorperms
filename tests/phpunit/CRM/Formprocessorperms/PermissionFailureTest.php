<?php

declare(strict_types = 1);

use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * Defensive paths of the permission hook: table absent, query failing.
 * Not transactional: the cases are staged with DDL (implicit commit), so the
 * real table is renamed aside and restored in a finally block.
 *
 * @group headless
 */
class CRM_Formprocessorperms_PermissionFailureTest extends TestCase implements HeadlessInterface {

  private const TABLE = 'civicrm_form_processor_instance';
  private const BACKUP = 'civicrm_fpp_instance_backup';

  public function setUpHeadless(): CiviEnvBuilder {
    return ck_headless()->apply();
  }

  public function testDefensivePaths(): void {
    CRM_Core_DAO::executeQuery('RENAME TABLE ' . self::TABLE . ' TO ' . self::BACKUP);
    try {
      // Table absent: the hook registers nothing and does not throw.
      $this->assertSame([], $this->invokeHook());

      // Table present but unreadable: the query throws and is caught.
      CRM_Core_DAO::executeQuery('CREATE TABLE ' . self::TABLE . ' (id INT)');
      $this->assertSame([], $this->invokeHook());
    }
    finally {
      CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS ' . self::TABLE);
      CRM_Core_DAO::executeQuery('RENAME TABLE ' . self::BACKUP . ' TO ' . self::TABLE);
    }

    $this->assertSame([], $this->invokeHook());
  }

  /**
   * @return array<string, mixed>
   */
  private function invokeHook(): array {
    unset(\Civi::$statics['formprocessorperms']);
    $permissions = [];
    formprocessorperms_civicrm_permission($permissions);
    return $permissions;
  }

}
