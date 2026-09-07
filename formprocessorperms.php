<?php

declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects
require_once 'formprocessorperms.civix.php';
// phpcs:enable

use CRM_Formprocessorperms_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function formprocessorperms_civicrm_config(\CRM_Core_Config $config): void {
  _formprocessorperms_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function formprocessorperms_civicrm_install(): void {
  _formprocessorperms_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function formprocessorperms_civicrm_enable(): void {
  _formprocessorperms_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_permission().
 *
 * form_processor enforces its per-instance permission strings but never
 * registers them, so Standalone strips them from roles on save and Drupal
 * never lists them. Direct SQL: an API call here recurses into permission
 * checking. The table check covers form_processor being absent.
 *
 * @param array<string, mixed> $permissions
 */
function formprocessorperms_civicrm_permission(array &$permissions): void {
  /** @var array<string, string>|null $cache */
  $cache = &\Civi::$statics['formprocessorperms']['permissions'];
  if (!isset($cache)) {
    $cache = [];
    if (CRM_Core_DAO::checkTableExists('civicrm_form_processor_instance')) {
      try {
        $dao = CRM_Core_DAO::executeQuery(
          "SELECT title, permission FROM civicrm_form_processor_instance
           WHERE permission IS NOT NULL AND permission <> ''",
        );
        // Non-empty `permission` is guaranteed by the WHERE clause.
        /** @var \CRM_Core_DAO&object{permission: string, title: string} $dao */
        while ($dao->fetch()) {
          $cache[$dao->permission] = $dao->title;
        }
      }
      catch (\Throwable $e) {
        // The table exists, so this is a real failure that would silently
        // drop the permissions from every role screen.
        \Civi::log()->error('formprocessorperms: reading form processor permissions failed', [
          'exception' => $e,
        ]);
        $cache = [];
      }
    }
  }
  foreach ($cache as $perm => $title) {
    $permissions[$perm] ??= [
      'label' => E::ts('Form Processor: %1', [1 => $title]),
      'description' => E::ts('Invoke the "%1" form processor via the API.', [1 => $title]),
    ];
  }
}
