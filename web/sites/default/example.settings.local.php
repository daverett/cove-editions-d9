<?php

/**
 * @file
 * Example local development overrides for COVE.
 *
 * Copy this file to settings.local.php (same directory) to enable.
 * settings.local.php is gitignored and loaded by settings.php when present.
 */

/**
 * SendGrid SMTP password.
 *
 * The actual API key is intentionally not committed: smtp.settings is ignored
 * by drupal/config_ignore so the production value never gets overwritten.
 *
 * To send real emails locally:
 *  - get a key from https://app.sendgrid.com/settings/api_keys
 *  - uncomment and replace the placeholder below.
 */
// $config['smtp.settings']['smtp_password'] = 'SG.YOUR_API_KEY_HERE';
