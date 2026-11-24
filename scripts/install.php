<?php

/**
 * Installs the site on SQLite from the command line.
 *
 * Writes sites/default/settings.php, creates the database and leaves the update
 * module switched off. The administrator password set here is a throwaway: the
 * real one is applied at container start (see set-admin.php).
 *
 * Usage: php scripts/install.php <database file>
 */

if (PHP_SAPI !== 'cli' || $argc !== 2) {
  fwrite(STDERR, "usage: php scripts/install.php <database file>\n");
  exit(2);
}

$root = '/var/www/html';
$database = $argv[1];
chdir($root);
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SERVER_SOFTWARE'] = 'Apache';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';

$autoloader = require $root . '/autoload.php';
require_once $root . '/core/includes/install.core.inc';

if (!is_dir(dirname($database))) {
  mkdir(dirname($database), 0750, TRUE);
}
if (!file_exists($root . '/sites/default/settings.php')) {
  copy($root . '/sites/default/default.settings.php', $root . '/sites/default/settings.php');
}

$password = bin2hex(random_bytes(16));
install_drupal($autoloader, [
  'interactive' => FALSE,
  'parameters' => [
    'profile' => 'standard',
    'langcode' => 'en',
  ],
  'forms' => [
    'install_settings_form' => [
      'driver' => 'sqlite',
      'sqlite' => ['database' => $database, 'prefix' => ''],
    ],
    'install_configure_form' => [
      'site_name' => 'Riverside Community Portal',
      'site_mail' => 'office@example.org',
      'account' => [
        'name' => 'admin',
        'mail' => 'admin@example.org',
        'pass' => ['pass1' => $password, 'pass2' => $password],
      ],
      'date_default_timezone' => 'UTC',
      // A checkbox is switched off programmatically with NULL, not FALSE.
      'enable_update_status_module' => NULL,
      'enable_update_status_emails' => NULL,
    ],
  ],
]);
echo "installed\n";
