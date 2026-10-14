<?php

namespace Drupal\gss\Plugin\Shortcode;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\shortcode\Attribute\Shortcode;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * Provides a shortcode for adding Google Sheet table from Shentity.
 */
#[Shortcode(
  id: 'gss',
  title: new TranslatableMarkup('gss'),
  description: new TranslatableMarkup('Pulls google sheet into field'),
)]
class GssShortcode extends ShortcodeBase {

  /**
   * Constructs a new Shortcode plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RendererInterface $renderer,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $renderer);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function process(array $attributes, string $text, string $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED): string {
    $attributes = $this->getAttributes([
      'key'    => '',
      'search' => '',
    ],
      $attributes
    );

    $key = !empty($attributes['key']) ? Xss::filter($attributes['key']) : NULL;
    $search = !empty($attributes['search']) ? Xss::filter($attributes['search']) : 0;

    if ($key !== NULL) {
      if (!is_numeric($key)) {
        return '';
      }
      $entity = $this->entityTypeManager->getStorage('shentity')->load($key);
      if (!isset($entity->sheet)) {
        return '';
      }
      // Adds class to add search.
      $search_class = ($entity->get('type')->value === 'list') ? 'list-search' : 'table-search';
      $gss_plain = $search ? "<div class='{$search_class}'></div>" . $entity->sheet->value : $entity->sheet->value;

      // Render the table.
      $gss = [
        '#type' => 'inline_template',
        '#template' => '{{ gss_plain|raw }}',
        '#context' => [
          'gss_plain' => $gss_plain,
        ],
      ];
      return $this->render($gss);
    }
    else {
      return '';
    }
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function tips($long = FALSE) {
    $output = [];
    $output[] = '<p><strong>[gss key="shentity-id" search=0][/gss]</strong> ';
    $output[] = $this->t('Displays google sheet from shentity.') . '</p>';

    return implode(' ', $output);
  }

}
