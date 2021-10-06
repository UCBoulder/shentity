<?php

namespace Drupal\shentity\Plugin;

use Drupal\oit\Plugin\GoogleSheetsApi;
use Drupal\Component\Utility\Xss;

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
   * Setup table or list from Google sheet.
   */
  public function __construct($key, $fields, $type, $sheet_number, $shift) {

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
        \Drupal::logger('shentity')->warning("No header on sheet $key");
      }
      if (isset($table_data['rows'])) {
        $table_rows = $table_data['rows'];
      }
      else {
        $table_rows = [];
        \Drupal::logger('shentity')->warning("No rows on sheet $key");
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
        $this->data = \Drupal::service('renderer')->renderPlain($build);
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
