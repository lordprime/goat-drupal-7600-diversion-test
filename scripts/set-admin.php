<?php

/**
 * Sets the administrator password from the first line of standard input.
 *
 * Usage: php set-admin.php < password-file
 */

require __DIR__ . '/lib/Site.php';

$password = trim((string) fgets(STDIN));
if ($password === '') {
  fwrite(STDERR, "set-admin: empty password\n");
  exit(1);
}
Site::boot();
$account = \Drupal\user\Entity\User::load(1);
if (!$account) {
  fwrite(STDERR, "set-admin: no administrator account\n");
  exit(1);
}
$account->setPassword($password)->save();
echo "admin password set\n";
