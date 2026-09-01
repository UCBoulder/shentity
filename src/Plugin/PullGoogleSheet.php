<?php

namespace Drupal\shentity\Plugin;

use Drupal\Component\Utility\Random;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\oit\Plugin\GoogleSheetsApi;

/**
 * Pulls in google sheet.
 */
class PullGoogleSheet {

  /**
   * Fetch google sheet data.
   *
   * @var string
   */
  private $data;

  /**
   * Whether the last fetch produced usable data.
   *
   * FALSE when the remote sheet could not be retrieved, so callers can keep
   * whatever they already had rather than storing an empty table.
   *
   * @var bool
   */
  private $success = FALSE;

  /**
   * The Teams logging channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The 'renderer' service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Construct the PullGoogleSheet object.
   */
  public function __construct(LoggerChannelFactoryInterface $channelFactory, RendererInterface $renderer) {
    $this->renderer = $renderer;
    $this->logger = $channelFactory->get('shentity');
  }

  /**
   * Setup table or list from Google sheet.
   */
  public function fetch($key, $fields, $type, $sheet_number, $shift, $shentity = FALSE) {
    $this->success = FALSE;
    if (!empty($key)) {
      $parsed_url = parse_url($key);
      if ($parsed_url !== FALSE
        && isset($parsed_url['scheme'], $parsed_url['host'], $parsed_url['path'])
        && $parsed_url['scheme'] === 'https'
        && $parsed_url['host'] === 'docs.google.com'
        && strpos($parsed_url['path'], '/spreadsheets/') === 0) {
        // Valid Google Sheets URL.
      }
      else {
        $key = NULL;
      }
    }
    else {
      $key = NULL;
    }
    $gid = ($sheet_number !== NULL && ctype_digit((string) $sheet_number)) ? Xss::filter((string) $sheet_number) : NULL;
    $shift = ($shift !== NULL && ctype_digit((string) $shift)) ? Xss::filter((string) $shift) : NULL;

    if ($key !== NULL && $type == 'table') {
      $sheet_letters = $fields;
      $table = new GoogleSheetsApi();
      $table->sheetDefined($key, $sheet_letters, $gid, $shift, $shentity);
      if (!$table->isSuccessful()) {
        return;
      }
      $table_data = $table->getSheetData();
      // Random characters for id.
      $random = new Random();
      $id = $random->string();
      $id = 'shentity-' . preg_replace('/[^a-zA-Z0-9\-]/', '', substr($id, 0, 10));
      if (isset($table_data['header'])) {
        $table_header = $table_data['header'];
      }
      else {
        $table_header = [];
        $this->logger->warning("No header on sheet $key");
      }
      if (isset($table_data['rows'])) {
        $table_rows = $table_data['rows'];
      }
      else {
        $table_rows = [];
        $this->logger->warning("No rows on sheet $key");
      }
      $build['tablesort_table'] = [
        '#type' => 'table',
        '#header' => $table_header,
        '#rows' => $table_rows,
        '#attributes' => [
          'id' => $id,
          'class' => ['shentity-table'],
        ],
      ];

      if (isset($build)) {
        $this->data = $this->renderer->renderInIsolation($build);
        $this->success = TRUE;
      }
    }
    elseif ($key !== NULL && $type == 'list') {
      $sheet_letters = $fields;
      $list = new GoogleSheetsApi();
      $list->sheetDefined($key, $sheet_letters, $gid, $shift, $shentity);
      if (!$list->isSuccessful()) {
        return;
      }
      $list_data = $list->getSheetData();
      $items = [];
      if (isset($list_data['rows'])) {
        foreach ($list_data['rows'] as $row) {
          $cell_values = [];
          foreach ($row as $cell) {
            if (!isset($cell['data']) || $cell['data'] === '') {
              continue;
            }
            $cell_values[] = is_array($cell['data'])
              ? (string) $this->renderer->renderInIsolation($cell['data'])
              : (string) $cell['data'];
          }
          if (!empty($cell_values)) {
            $summary = preg_replace('/^<p>(.*)<\/p>$/s', '$1', trim(array_shift($cell_values)));
            $details_content = !empty($cell_values) ? implode(' ', $cell_values) : '';
            $items[] = [
              '#markup' => '<details><summary>' . $summary . '</summary>' . $details_content . '</details>',
            ];
          }
        }
      }
      $random = new Random();
      $list_id = 'shentity-list-' . preg_replace('/[^a-zA-Z0-9\-]/', '', substr($random->string(), 0, 10));
      $build['list'] = [
        '#theme' => 'item_list',
        '#items' => $items,
        '#attributes' => ['id' => $list_id, 'class' => ['shentity-list no-list-style']],
      ];
      if (isset($build)) {
        $this->data = $this->renderer->renderInIsolation($build);
        $this->success = TRUE;
      }
    }
    else {
      // No usable key or an unknown type: an empty result is the correct
      // answer here, so it is safe to store.
      $this->data = '';
      $this->success = TRUE;
    }
  }

  /**
   * Whether the last fetch produced data that is safe to store.
   *
   * @return bool
   *   FALSE when the remote sheet could not be retrieved.
   */
  public function isSuccessful(): bool {
    return $this->success;
  }

  /**
   * Get sheet that was fetched.
   */
  public function getData() {
    return $this->data;
  }

}
