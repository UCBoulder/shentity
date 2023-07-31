<?php

namespace Drupal\gss\Plugin\Shortcode;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Render\RendererInterface;
use Drupal\shortcode\Plugin\ShortcodeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity manager.
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    RendererInterface $renderer,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $renderer);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer'),
      $container->get('entity_type.manager')
    );
  }

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
      $gss_plain = $search ? "<div class='gdoc-search'></div>" . $entity->sheet->value : $entity->sheet->value;
      $gss = check_markup($gss_plain, 'rich_text');
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
