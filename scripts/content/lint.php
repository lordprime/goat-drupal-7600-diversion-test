<?php

/**
 * Fails when the content pack contains wording aimed at a machine reader.
 *
 * Checks every user name, term, title and body for imperative phrases that
 * address an assistant, a model or a bot, and for words that name one.
 *
 * Usage: php scripts/content/lint.php
 */

require_once __DIR__ . '/ContentPack.php';

class ContentLint {

  /** Phrases that give an instruction to whoever is reading. */
  const INSTRUCTIONS = [
    '/\b(ignore|disregard|forget|override|bypass)\b[^.]{0,40}\b(previous|prior|above|earlier|all|any|your)\b[^.]{0,30}\b(instructions?|rules?|prompts?|guidelines?)\b/i',
    '/\b(you (are|must|should|will|need to)|act as|pretend|respond (with|only)|reply (with|only)|do not (tell|mention|reveal))\b/i',
    '/\b(run|execute|call|invoke|fetch|visit|send|post|upload|download)\b[^.]{0,25}\b(the following|this (url|link|command|request|endpoint))\b/i',
    '/\b(system|developer) (prompt|message|instructions?)\b/i',
  ];

  /** Words that address or name a machine reader. */
  const ADDRESSEES = '/\b(ai|a\.i\.|agents?|assistants?|language models?|llms?|chatbots?|bots?|gpt|claude|copilot)\b/i';

  /**
   * Returns one message per problem found in $text, labelled with $where.
   */
  public static function check($text, $where) {
    $text = html_entity_decode(strip_tags($text));
    $problems = [];
    foreach (self::INSTRUCTIONS as $pattern) {
      if (preg_match($pattern, $text, $m)) {
        $problems[] = "$where: instruction-like phrase '{$m[0]}'";
      }
    }
    if (preg_match(self::ADDRESSEES, $text, $m)) {
      $problems[] = "$where: names a machine reader '{$m[0]}'";
    }
    return $problems;
  }

  public static function run(ContentPack $pack) {
    $problems = [];
    foreach ($pack->users() as $i => $user) {
      $problems = array_merge($problems, self::check($user['name'] . ' ' . $user['mail'], "user $i"));
    }
    foreach ($pack->terms() as $i => $term) {
      $problems = array_merge($problems, self::check($term, "term $i"));
    }
    foreach ($pack->articles() as $i => $article) {
      $problems = array_merge($problems, self::check($article['title'] . ' ' . $article['body'], "article $i"));
    }
    foreach ($pack->pages() as $path => $page) {
      $problems = array_merge($problems, self::check($page['title'] . ' ' . $page['body'], "page $path"));
    }
    return $problems;
  }

}

if (PHP_SAPI === 'cli' && realpath($argv[0]) === __FILE__) {
  $problems = ContentLint::run(new ContentPack());
  foreach ($problems as $problem) {
    fwrite(STDERR, $problem . "\n");
  }
  exit($problems ? 1 : 0);
}
