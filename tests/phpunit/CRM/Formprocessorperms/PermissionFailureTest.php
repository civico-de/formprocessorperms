<?php

declare(strict_types = 1);

use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * Covers the two defensive paths of the permission hook: the window where
 * form_processor's table is absent, and a query that really fails.
 *
 * Not transactional on purpose — both cases are staged with DDL (implicit
 * commit), so the table is renamed aside and restored in a finally block
 * instead. The real table is never altered.
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

    // Restored: the hook reads the real table again.
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
