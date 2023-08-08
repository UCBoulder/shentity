<?php

namespace Drupal\shentity\Plugin;

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
  public function fetch($key, $fields, $type, $sheet_number, $shift) {
    $key = !empty($key) ? Xss::filter($key) : NULL;
    $gid = $sheet_number >= 0 ? Xss::filter($sheet_number) : NULL;
    $shift = Xss::filter($shift);

    if ($key !== NULL && $gid !== NULL && $type == 'table') {
      $sheet_letters = $fields;
      $table = new GoogleSheetsApi();
      $table->sheetDefined($key, $sheet_letters, $gid, $shift);
      $table_data = $table->getSheetData();
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
          'id' => 'gdoc-table',
        ],
      ];

      if (isset($build)) {
        $this->data = $this->renderer->render($build);
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
