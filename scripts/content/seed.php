<?php

/**
 * Loads the fixed content pack into a freshly installed site and sets the
 * site's registration, cron and update behaviour.
 *
 * Usage: php scripts/content/seed.php
 */

require __DIR__ . '/../lib/Site.php';
require __DIR__ . '/ContentPack.php';
require __DIR__ . '/lint.php';

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

class Seeder {

  private $pack;

  public function __construct(ContentPack $pack) {
    $this->pack = $pack;
  }

  /**
   * Anyone may register without approval or mail, update checks and the
   * request-driven cron stay off, and core is never updated.
   */
  public function configure() {
    $factory = \Drupal::configFactory();
    $factory->getEditable('user.settings')
      ->set('register', USER_REGISTER_VISITORS)
      ->set('verify_mail', FALSE)
      ->save();
    $factory->getEditable('automated_cron.settings')->set('interval', 0)->save();
    $installer = \Drupal::service('module_installer');
    if (\Drupal::moduleHandler()->moduleExists('update')) {
      $installer->uninstall(['update']);
    }
  }

  /**
   * Creates the users, terms, articles and static pages; returns the counts.
   */
  public function load() {
    $users = [];
    foreach ($this->pack->users() as $row) {
      $user = User::create([
        'name' => $row['name'],
        'mail' => $row['mail'],
        'pass' => $row['pass'],
        'status' => 1,
        'created' => $row['created'],
      ]);
      $user->save();
      $users[] = $user->id();
    }
    $terms = [];
    foreach ($this->pack->terms() as $name) {
      $term = Term::create(['vid' => 'tags', 'name' => $name]);
      $term->save();
      $terms[] = $term->id();
    }
    foreach ($this->pack->articles() as $row) {
      Node::create([
        'type' => 'article',
        'title' => $row['title'],
        'uid' => $users[$row['author']],
        'status' => 1,
        'promote' => $row['promote'],
        'created' => $row['created'],
        'changed' => $row['created'],
        'body' => ['value' => $row['body'], 'format' => 'basic_html'],
        'field_tags' => array_map(function ($i) use ($terms) {
          return ['target_id' => $terms[$i]];
        }, $row['tags']),
      ])->save();
    }
    $created = ContentPack::EPOCH + ContentPack::STEP * ContentPack::ARTICLES;
    foreach ($this->pack->pages() as $path => $page) {
      Node::create([
        'type' => 'page',
        'title' => $page['title'],
        'uid' => 1,
        'status' => 1,
        'promote' => 0,
        'created' => $created,
        'changed' => $created,
        'body' => ['value' => $page['body'], 'format' => 'basic_html'],
        'path' => ['alias' => '/' . $path],
      ])->save();
    }
  }

}

$problems = ContentLint::run($pack = new ContentPack());
if ($problems) {
  fwrite(STDERR, implode("\n", $problems) . "\nseed: the content pack failed its lint\n");
  exit(1);
}
Site::boot();
$seeder = new Seeder($pack);
$seeder->configure();
$seeder->load();
echo 'seeded ' . $pack->digest() . "\n";
