<?php

namespace Drupal\gss\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;
use Drupal\Component\Utility\Xss;

/**
 * Provides a shortcode for adding Google Sheet table from Shentity.
 *
 * @Shortcode(
 *   id = "gss",
 *   title = @Translation("gss"),
 *   description = @Translation("Pulls google sheet into field")
 * )
 */
class GssShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process(array $attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {

    $attributes = $this->getAttributes([
      'key'    => '',
      'search' => '',
    ],
      $attributes
    );

    $key = !empty($attributes['key']) ? Xss::filter($attributes['key']) : NULL;
    $search = $attributes['search'] > 0 ? Xss::filter($attributes['search']) : 0;

    if ($key !== NULL) {
      if (!is_numeric($key)) {
        return '';
      }
      $entity = \Drupal::entityTypeManager()->getStorage('shentity')->load($key);
      if (!isset($entity->sheet)) {
        return '';
      }
      $gss = check_markup($entity->sheet->value, 'rich_text');
      return $gss;
    }
    else {
      return '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = [];
    $output[] = '<p><strong>[gss key="shentity-id" search=0][/gss]</strong> ';
    if ($long) {
      $output[] = $this->t('Displays google sheet from shentity.') . '</p>';
    }
    else {
      $output[] = $this->t('Displays google sheet from shentity.') . '</p>';
    }

    return implode(' ', $output);
  }

}
