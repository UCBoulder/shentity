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
    $gid = ($sheet_number !== NULL && $sheet_number >= 0) ? Xss::filter((string) $sheet_number) : NULL;
    $shift = ($shift !== NULL && $shift >= 0) ? Xss::filter((string) $shift) : NULL;

    if ($key !== NULL && $type == 'table') {
      $sheet_letters = $fields;
      $table = new GoogleSheetsApi();
      $table->sheetDefined($key, $sheet_letters, $gid, $shift, $shentity);
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
      }
    }
    elseif ($key !== NULL && $gid !== NULL && $type == 'ttext') {
      $sheet_letters = $fields;
      $pull_table = new GoogleSheetsApi();
      $pull_table->sheetDefined($key, $key . '--' . $gid, $sheet_letters, $gid, $shift);
      $table = $pull_table->getSheetData();
      $full_row = '<div class="shortsheets">';
      if (isset($table['rows'])) {
        foreach ($table['rows'] as $row) {
          foreach ($row['data'] as $key => $column) {
            $full_row .= sprintf(
              '<dl class="sheetrow sheetrow%s"><dt>%s</dt><dd>%s</dd></dl>',
              $key,
              $table['header'][$key],
              $column
            );
          }
        }
      }
      $full_row .= '</div>';
      $this->data = $full_row;
    }
    else {
      $this->data = '';
    }
  }

  /**
   * Get sheet that was fetched.
   */
  public function getData() {
    return $this->data;
  }

}
