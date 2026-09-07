<?php

declare(strict_types = 1);

/**
 * phpstan bootstrap: CiviCRM core's class loader plus the classes of every
 * <requires> extension present under CK_EXT_DIR (civikitchen container layout).
 */
$coreDir = getenv('CIVICRM_CORE_DIR') ?: '/var/www/html/core';

require_once $coreDir . '/vendor/autoload.php';
require_once $coreDir . '/CRM/Core/ClassLoader.php';
\CRM_Core_ClassLoader::singleton()->register();
require_once $coreDir . '/api/api.php';

// Settings-defined runtime constant; phpstan only needs it to exist.
defined('CIVICRM_UF_BASEURL') || define('CIVICRM_UF_BASEURL', 'http://localhost');

// Required extensions live off core's classloader path; a missing one is only
// noted so phpstan reports "unknown class" instead of the bootstrap dying.
$ckExtDir = getenv('CK_EXT_DIR') ?: '/var/www/html/ext';
libxml_use_internal_errors(use_errors: TRUE);
$ckInfo = simplexml_load_file(__DIR__ . '/info.xml');
foreach ($ckInfo === FALSE ? [] : $ckInfo->requires->ext ?? [] as $ckRequired) {
  $ckKey = trim((string) $ckRequired);
  $ckDir = $ckExtDir . '/' . $ckKey;
  if ($ckKey === '' || !is_dir($ckDir)) {
    fwrite(
      STDERR,
      "phpstanBootstrap: required extension {$ckKey} is not under {$ckExtDir}; its classes stay unresolved\n",
    );
    continue;
  }
  if (is_file($ckDir . '/vendor/autoload.php')) {
    require_once $ckDir . '/vendor/autoload.php';
  }
  spl_autoload_register(static function (string $class) use ($ckDir): void {
    // civix DAOs extend CRM_<Prefix>_DAO_Base, which no file defines: the
    // extension's civix loader aliases it to core's CRM_Core_DAO_Base at runtime.
    if (preg_match('/^CRM_(\w+)_DAO_Base$/', $class, $m) === 1 && is_dir($ckDir . '/CRM/' . $m[1])) {
      class_alias('CRM_Core_DAO_Base', $class);
      return;
    }
    $file = match (TRUE) {
      str_starts_with($class, 'Civi\\') => $ckDir . '/Civi/' . str_replace('\\', '/', substr($class, 5)) . '.php',
      str_starts_with($class, 'CRM_'), str_starts_with($class, 'api_') => $ckDir
        . '/'
        . str_replace('_', '/', $class)
        . '.php',
      default => NULL,
    };
    if ($file !== NULL && is_file($file)) {
      require_once $file;
    }
  });
}
