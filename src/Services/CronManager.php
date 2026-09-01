<?php

namespace Drupal\shentity\Services;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Manages cron job callbacks for the shentity module.
 */
class CronManager {

  /**
   * Number of shentities refreshed per run.
   */
  const BATCH_SIZE = 5;

  /**
   * Re-save stale shentities so presave re-fetches their Google Sheet.
   */
  public static function refreshSheets() {
    $date = new DrupalDateTime('-12 hours');
    $stale = $date->format('U');

    $query = \Drupal::entityQuery('shentity');
    $query->condition('changed', $stale, '<=');
    $query->accessCheck(FALSE);
    $query->range(0, self::BATCH_SIZE);
    $results = $query->execute();

    $storage = \Drupal::entityTypeManager()->getStorage('shentity');
    $i = 0;
    foreach ($results as $sid) {
      // Space the requests out: the published Google Sheets CSV endpoint
      // throttles bursts, and every save triggers a fetch in presave.
      if ($i > 0) {
        sleep(3);
      }
      $entity = $storage->load($sid);
      if ($entity) {
        $now = new DrupalDateTime();
        $entity->setChangedTime($now->format('U'));
        $entity->save();
      }
      $i++;
    }

    \Drupal::logger('shentity')->notice('Refreshed @count shentity sheet(s).', ['@count' => $i]);
  }

  /**
   * Invalidate caches on nodes embedding a Google Sheet via [gss] tags.
   */
  public static function invalidateGssNodes() {
    $date = new DrupalDateTime('-12 hours');
    $stale = $date->format('U');

    $query = \Drupal::entityQuery('node');
    $query->condition('body', '[/gss]', 'CONTAINS');
    $query->condition('changed', $stale, '<=');
    $query->accessCheck(FALSE);
    $results = $query->execute();

    $storage = \Drupal::entityTypeManager()->getStorage('node');
    foreach ($storage->loadMultiple($results) as $node) {
      Cache::invalidateTags($node->getCacheTags());
    }

    \Drupal::logger('shentity')->notice('Invalidated caches on @count gss node(s).', ['@count' => count($results)]);
  }

}
