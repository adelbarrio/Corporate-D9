<?php

namespace Drupal\hwc_import_export;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * General class for Cron hooks.
 */
class HwcCron implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected MigrationPluginManager $migrationManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(MigrationPluginManager $migration_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->migrationManager = $migration_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.migration'),
      $container->get('entity_type.manager')
    );
  }


  /**
   * Implements hook_cron().
   */
  public function cron() {
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
    ];

    // Start every migration.
    foreach ($migrations as $migration_id) {
      /** @var \Drupal\migrate\Plugin\Migration  $migration */
      $migration = $this->migrationManager->createInstance($migration_id);
      $migration->getIdMap()->prepareUpdate();
      $executable = new MigrateExecutable($migration, new MigrateMessage());
      $executable->import();

      // Check the nodes that thay have been deleted from source.
      $dels = $migration->getIdMap()->getRowsNeedingUpdate(1000);
      foreach ($dels as $key => $del) {
        $del = (array) $del;
        // Remove it from migration table.
        $migration->getIdMap()->deleteDestination(['nid' => $del['destid1']]);
        // Remove the node.
        $node = $this->entityTypeManager->getStorage('node')->load($del['destid1']);
        $node->delete();
      }
    }
  }
}
