<?php

/**
 * A fixed-seed set of users, terms and articles for a small community site.
 *
 * Nothing here touches Drupal: the pack is plain data, so the same seed gives
 * the same text on every machine and a linter can read it without a database.
 */
class ContentPack {

  const SEED = 20181029;
  const USERS = 100;
  const TERMS = 40;
  const ARTICLES = 497;

  /** First article time (2019-01-07) and the gap between articles, in seconds. */
  const EPOCH = 1546819200;
  const STEP = 57600;

  const FIRST = ['Mara', 'Tobias', 'Ilse', 'Rafael', 'Nadia', 'Colm', 'Petra', 'Henrik', 'Zara', 'Owen', 'Lucia', 'Dmitri', 'Freya', 'Anselm', 'Yara', 'Bruno', 'Edith', 'Kofi', 'Sunniva', 'Matteo'];
  const LAST = ['Ostrow', 'Vance', 'Lindqvist', 'Okafor', 'Brandt', 'Mercer', 'Haldane', 'Ruiz', 'Castell', 'Abara', 'Whitlow', 'Novak', 'Ferreira', 'Dunmore', 'Ishikawa'];

  const TERM_NAMES = [
    'Allotments', 'Archive', 'Bakery', 'Birdwatching', 'Book club', 'Bus routes', 'Canal path',
    'Choir', 'Cycling', 'Donations', 'Events', 'Exhibitions', 'Football', 'Gardening',
    'Harvest', 'Heritage', 'Library', 'Market', 'Meetings', 'Music', 'Notices', 'Parking',
    'Planning', 'Playground', 'Recipes', 'Recycling', 'Repairs', 'Rowing', 'School', 'Seniors',
    'Skate park', 'Swimming', 'Theatre', 'Tool library', 'Traffic', 'Trees', 'Volunteers',
    'Walking', 'Weather', 'Youth club',
  ];

  const ADJECTIVES = ['quiet', 'busy', 'early', 'late', 'seasonal', 'weekly', 'annual', 'open', 'local', 'new', 'old', 'riverside', 'northern', 'covered', 'shared'];
  const NOUNS = ['meeting', 'sale', 'walk', 'workshop', 'rehearsal', 'tournament', 'collection', 'exhibition', 'clean-up', 'tasting', 'lecture', 'fair', 'class', 'survey', 'repair day'];
  const PLACES = ['the village hall', 'the old mill', 'the library', 'the community garden', 'the church rooms', 'the sports ground', 'the market square', 'the boathouse', 'the school hall', 'the canal basin'];
  const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  const SENTENCES = [
    'The next {noun} takes place at {place} on {day} evening.',
    'Around {num} people came to the last {noun} and the organisers were pleased with the turnout.',
    'Doors open half an hour early, and tea and coffee are served at {place}.',
    'Anyone who can spare an hour is welcome to join the {noun} team.',
    'The committee has agreed to repeat the {noun} after this season.',
    'Last year the {noun} raised enough to replace the benches at {place}.',
    'Parking is limited near {place}, so please walk or cycle if you can.',
    'Notes from the previous {noun} are kept in the folder at {place}.',
    'The {noun} was moved to {place} because of the roadworks.',
    'Questions about the {noun} can be left with the volunteers at {place}.',
  ];

  /** The three static pages, with their paths. */
  const PAGES = [
    'blog' => [
      'title' => 'Blog',
      'body' => '<p>News and notes from around the village. The most recent posts are on the front page, and every post carries tags so you can find the ones you care about.</p><p>Posts are written by members. If you would like to write one, speak to a volunteer at the library desk.</p>',
    ],
    'help' => [
      'title' => 'Help',
      'body' => '<p>How to use this site.</p><ul><li>Browse posts on the front page or by tag.</li><li>Create an account from the registration page to take part.</li><li>Search is in the menu at the top of every page.</li></ul><p>For anything else, ask at the library desk during opening hours.</p>',
    ],
    'changelog' => [
      'title' => 'Changelog',
      'body' => '<p>Recent changes to the site.</p><ul><li>Events are now grouped by tag.</li><li>The notice board has moved to the front page.</li><li>Registration is open to everyone in the parish.</li></ul>',
    ],
  ];

  /** Position in the seeded sequence; each generator starts at its own offset. */
  private $counter = 0;

  /**
   * Next number from the seeded sequence, in [0, $max).
   */
  private function next($max) {
    $digest = hash('sha256', self::SEED . ':' . $this->counter++);
    return hexdec(substr($digest, 0, 8)) % $max;
  }

  private function pick(array $items) {
    return $items[$this->next(count($items))];
  }

  /**
   * The users, as name, mail, created time and an unusable password.
   */
  public function users() {
    $this->counter = 0;
    $users = [];
    for ($i = 0; $i < self::USERS; $i++) {
      $name = strtolower($this->pick(self::FIRST) . '.' . $this->pick(self::LAST)) . sprintf('%02d', $i);
      $users[] = [
        'name' => $name,
        'mail' => $name . '@example.org',
        'created' => self::EPOCH - 86400 * (self::USERS - $i),
        'pass' => hash('sha256', self::SEED . ':pass:' . $i),
      ];
    }
    return $users;
  }

  public function terms() {
    return self::TERM_NAMES;
  }

  /**
   * The articles in creation order. 'author' and 'tags' are 0-based indexes
   * into users() and terms().
   */
  public function articles() {
    $this->counter = 100000;
    $articles = [];
    for ($i = 0; $i < self::ARTICLES; $i++) {
      $noun = $this->pick(self::NOUNS);
      $title = ucfirst($this->pick(self::ADJECTIVES) . ' ' . $noun) . ' at ' . $this->pick(self::PLACES);
      $paragraphs = [];
      $paragraphCount = 2 + $this->next(3);
      for ($p = 0; $p < $paragraphCount; $p++) {
        $lines = [];
        $sentenceCount = 2 + $this->next(3);
        for ($s = 0; $s < $sentenceCount; $s++) {
          $lines[] = $this->sentence($noun);
        }
        $paragraphs[] = '<p>' . implode(' ', $lines) . '</p>';
      }
      $tags = [];
      $tagCount = 1 + $this->next(3);
      for ($t = 0; $t < $tagCount; $t++) {
        $tags[] = $this->next(self::TERMS);
      }
      $articles[] = [
        'title' => $title,
        'body' => implode('', $paragraphs),
        'author' => $this->next(self::USERS),
        'tags' => array_values(array_unique($tags)),
        'created' => self::EPOCH + self::STEP * $i,
        'promote' => $this->next(5) === 0 ? 1 : 0,
      ];
    }
    return $articles;
  }

  public function pages() {
    return self::PAGES;
  }

  /**
   * One sentence from the templates, filled from the word lists.
   */
  private function sentence($noun) {
    return strtr($this->pick(self::SENTENCES), [
      '{noun}' => $noun,
      '{place}' => $this->pick(self::PLACES),
      '{day}' => $this->pick(self::DAYS),
      '{num}' => 10 + $this->next(90),
    ]);
  }

  /**
   * One hash over everything in the pack, for the image label and the tests.
   */
  public function digest() {
    return hash('sha256', json_encode([$this->users(), $this->terms(), $this->articles(), $this->pages()]));
  }

}
