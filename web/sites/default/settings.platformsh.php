<?php

/**
 * @file
 * Platform.sh / Upsun environment overrides.
 *
 * Loaded by settings.php when running on a Platform.sh / Upsun environment.
 * Uses the platformsh/config-reader package (see composer.json).
 */

use Drupal\Component\Utility\NestedArray;
use Platformsh\ConfigReader\Config;

$platformsh = new Config();

if (!$platformsh->isValidPlatform()) {
  return;
}

// Database credentials from the 'database' relationship.
if ($platformsh->hasRelationship('database')) {
  $creds = $platformsh->credentials('database');
  $databases['default']['default'] = [
    'driver' => $creds['scheme'],
    'database' => $creds['path'],
    'username' => $creds['username'],
    'password' => $creds['password'],
    'host' => $creds['host'],
    'port' => $creds['port'],
    'pdo' => [PDO::MYSQL_ATTR_COMPRESS => !empty($creds['query']['compression'])],
  ];
}

// Branch-specific configuration overrides.
if (isset($platformsh->branch)) {
  if ($platformsh->branch == 'master') {
    // Production: hide errors.
    $config['system.logging']['error_level'] = 'hide';
    // Do not use stage_file_proxy on prod.
    $config['stage_file_proxy.settings']['origin'] = '';
  }
  else {
    // Non-production environments: verbose errors.
    $config['system.logging']['error_level'] = 'verbose';
    // Same as on settings.php for lando.
    $config['stage_file_proxy.settings']['origin'] = 'https://editions.covecollective.org';
    $config['stage_file_proxy.settings']['verify'] = FALSE;
    $config['stage_file_proxy.settings']['hotlink'] = TRUE;
  }
}

// Runtime-only configuration (paths, hash salt, deployment id).
if ($platformsh->inRuntime()) {
  if (!isset($settings['file_private_path'])) {
    $settings['file_private_path'] = $platformsh->appDir . '/private';
  }
  if (!isset($settings['file_temp_path'])) {
    $settings['file_temp_path'] = $platformsh->appDir . '/tmp';
  }
  if (!isset($settings['php_storage']['default'])) {
    $settings['php_storage']['default']['directory'] = $settings['file_private_path'];
  }
  if (!isset($settings['php_storage']['twig'])) {
    $settings['php_storage']['twig']['directory'] = $settings['file_private_path'];
  }

  // Project entropy as hash_salt fallback (settings.php already sets one).
  $settings['hash_salt'] = $settings['hash_salt'] ?? $platformsh->projectEntropy;
  // Deployment identifier (used for cache invalidation across deploys).
  $settings['deployment_identifier'] = $settings['deployment_identifier'] ?? $platformsh->treeId;
}

// @todo Tighten the trusted host patterns once the production hostname is known.
$settings['trusted_host_patterns'] = ['.*'];

// Map Platform.sh variables to Drupal $settings / $config.
// Variable name patterns:
// - drupal:my_setting   -> $settings['my_setting'].
// - d8config:foo:bar    -> $config['foo']['bar'].
foreach ($platformsh->variables() as $name => $value) {
  $parts = explode(':', $name);
  switch ($parts[0]) {
    case 'd8settings':
    case 'drupal':
      if (isset($parts[1])) {
        $settings[$parts[1]] = $value;
      }
      break;

    case 'd8config':
      if (count($parts) > 2) {
        NestedArray::setValue($config, array_slice($parts, 1), $value);
      }
      break;
  }
}
