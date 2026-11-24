<?php

/**
 * Shared helpers for the command-line site scripts.
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

class Site {

  /**
   * Where the site is installed.
   */
  const ROOT = '/var/www/html';

  /**
   * Boots Drupal for a command-line run and returns the kernel.
   */
  public static function boot() {
    chdir(self::ROOT);
    $autoloader = require self::ROOT . '/autoload.php';
    $request = Request::create('/', 'GET', [], [], [], [
      'HTTP_HOST' => 'localhost',
      'REMOTE_ADDR' => '127.0.0.1',
      'SCRIPT_NAME' => '/index.php',
      'SCRIPT_FILENAME' => self::ROOT . '/index.php',
    ]);
    $kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod', FALSE, self::ROOT);
    $kernel->boot();
    $kernel->preHandle($request);
    return $kernel;
  }

}
