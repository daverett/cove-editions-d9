<?php

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

/**
 * Include the Pantheon-specific settings file.
 *
 * n.b. The settings.pantheon.php file makes some changes
 *      that affect all environments that this site
 *      exists in.  Always include this file, even in
 *      a local development environment, to ensure that
 *      the site settings remain consistent.
 */
include __DIR__ . "/settings.pantheon.php";

/**
 * Skipping permissions hardening will make scaffolding
 * work better, but will also raise a warning when you
 * install Drupal.
 *
 * https://www.drupal.org/project/drupal/issues/3091285
 */
// $settings['skip_permissions_hardening'] = TRUE;

/**
 * If there is a local settings file, then include it
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
  include $local_settings;
}

$databases['migrate']['default'] = array(
  'database' => 'drupal',
  'username' => 'covemigrate2',
  'password' => '*rik@2tGK6RS',
  'prefix' => '',
  'host' => '164.92.87.250',
  'port' => '',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);

$databases['migrate-pantheon']['default'] = array(
  'database' => 'drupal',
  'username' => 'covemigrate3',
  'password' => '*rik@2tGKL!D',
  'prefix' => '',
  'host' => '164.92.87.250',
  'port' => '',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);

$migrate_settings = __DIR__ . "/settings.migrate-on-pantheon.php";
if (file_exists($migrate_settings) && isset($_ENV['PANTHEON_ENVIRONMENT'])) {
 include $migrate_settings;
}
ini_set('memory_limit', '512M');
// Automatically generated include for settings managed by ddev.
$ddev_settings = dirname(__FILE__) . '/settings.ddev.php';
if (getenv('IS_DDEV_PROJECT') == 'true' && is_readable($ddev_settings)) {
  require $ddev_settings;
}

$config_directories['sync'] = '../config/sync';

$settings['file_private_path'] = __DIR__ . '/files/private';