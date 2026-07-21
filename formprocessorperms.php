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
 * Registers the per-processor permission strings configured on Form Processor
 * instances. form_processor enforces them (hook_civicrm_alterAPIPermissions)
 * but never registers them, so on Standalone they are silently stripped from
 * roles on save, and on Drupal they never appear in the permissions UI.
 *
 * Reads via direct SQL: this hook runs while the permission list is being
 * built, so an API call here would recurse into permission checking. The
 * try/catch covers the window where form_processor is absent or not yet
 * installed.
 */
function formprocessorperms_civicrm_permission(array &$permissions): void {
  $cache = &\Civi::$statics['formprocessorperms']['permissions'];
  if (!isset($cache)) {
    $cache = [];
    try {
      $dao = CRM_Core_DAO::executeQuery(
        "SELECT title, permission FROM civicrm_form_processor_instance
         WHERE permission IS NOT NULL AND permission <> ''"
      );
      while ($dao->fetch()) {
        $cache[$dao->permission] = $dao->title;
      }
    }
    catch (\Throwable $e) {
      $cache = [];
    }
  }
  foreach ($cache as $perm => $title) {
    $permissions[$perm] ??= [
      'label' => E::ts('Form Processor: %1', [1 => $title]),
      'description' => E::ts('Invoke the "%1" form processor via the API.', [1 => $title]),
    ];
  }
}
