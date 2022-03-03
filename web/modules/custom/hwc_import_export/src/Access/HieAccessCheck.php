<?php

namespace Drupal\hwc_import_export\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;

/**
 * General class for custom access check for approvers.
 */
class HieAccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    $node = \Drupal::routeMatch()->getParameter('node');

    $migrations = [
      'hwc_publications',
      'hwc_publications_bg',
      'hwc_publications_cs',
      'hwc_publications_da',
      'hwc_publications_de',
      'hwc_publications_es',
      'hwc_publications_de',
      'hwc_publications_el',
      'hwc_publications_et',
      'hwc_publications_fi',
      'hwc_publications_hr',
      'hwc_publications_hu',
      'hwc_publications_is',
      'hwc_publications_it',
      'hwc_publications_lv',
      'hwc_publications_lt',
      'hwc_publications_nl',
      'hwc_publications_mt',
      'hwc_publications_no',
      'hwc_publications_pl',
      'hwc_publications_pt',
      'hwc_publications_ro',
      'hwc_publications_sk',
      'hwc_publications_sl',
      'hwc_publications_sv',
      'hwc_publications_fr',
      'hwc_add_publications',
      'hwc_news',
      'hwc_add_news',
      'hwc_highlights',
      'hwc_highlights_bg',
      'hwc_highlights_cs',
      'hwc_highlights_da',
      'hwc_highlights_de',
      'hwc_highlights_es',
      'hwc_highlights_de',
      'hwc_highlights_el',
      'hwc_highlights_et',
      'hwc_highlights_fi',
      'hwc_highlights_hr',
      'hwc_highlights_hu',
      'hwc_highlights_is',
      'hwc_highlights_it',
      'hwc_highlights_lv',
      'hwc_highlights_lt',
      'hwc_highlights_nl',
      'hwc_highlights_mt',
      'hwc_highlights_no',
      'hwc_highlights_pl',
      'hwc_highlights_pt',
      'hwc_highlights_ro',
      'hwc_highlights_sk',
      'hwc_highlights_sl',
      'hwc_highlights_sv',
      'hwc_highlights_fr',
      'hwc_add_highlights',
      'hwc_infographic',
      'hwc_add_infographic',
      'hwc_events',
      'hwc_press_release',
      'hwc_press_release_bg',
      'hwc_press_release_cs',
      'hwc_press_release_da',
      'hwc_press_release_de',
      'hwc_press_release_es',
      'hwc_press_release_de',
      'hwc_press_release_el',
      'hwc_press_release_et',
      'hwc_press_release_fi',
      'hwc_press_release_hr',
      'hwc_press_release_hu',
      'hwc_press_release_is',
      'hwc_press_release_it',
      'hwc_press_release_lv',
      'hwc_press_release_lt',
      'hwc_press_release_nl',
      'hwc_press_release_mt',
      'hwc_press_release_no',
      'hwc_press_release_pl',
      'hwc_press_release_pt',
      'hwc_press_release_ro',
      'hwc_press_release_sk',
      'hwc_press_release_sl',
      'hwc_press_release_sv',
      'hwc_press_release_fr',
      'hwc_youtube',
      'hwc_add_slideshare',
      'hwc_slideshare',
    ];

    $database = \Drupal::database();

    if ($node instanceof \Drupal\node\Entity\Node) {
      foreach ($migrations as $migration_id) {
        try {
          $database->queryRange("SELECT 1 FROM {" . 'migrate_map_' . $migration_id . "}", 0, 1);
          $query = $database->select('migrate_map_' . $migration_id, 'ptc');
          $query->condition('ptc.destid1', $node->id());
          $query->fields('ptc', ['destid1']);
          $result = $query->execute()->fetchAll();
          if ($result) {
            return AccessResult::forbidden();
          }
        }
        catch (\Exception $e) {
          continue;
        }
      }
    }
    return AccessResult::allowed();
  }
}
