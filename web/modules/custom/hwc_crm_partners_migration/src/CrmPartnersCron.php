<?php

namespace Drupal\hwc_crm_partners_migration;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate_drupal_ui\Batch\MigrateMessageCapture;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * General class for Cron hooks.
 */
class CrmPartnersCron implements ContainerInjectionInterface
{

  use StringTranslationTrait;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


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
  public function cron($migration_id) {

      /** @var \Drupal\migrate\Plugin\Migration  $migration */
      $migration = $this->migrationManager->createInstance($migration_id);
      $migration->setStatus(0);
      $migration->getIdMap()->prepareUpdate();
      $migration->setStatus(MigrationInterface::STATUS_IDLE);

      $requirements = $migration->checkRequirements();

      $executable = new MigrateExecutable($migration, new MigrateMessage());
      $executable->message->display('Antes linea 68', MigrationInterface::MESSAGE_NOTICE);
      try {
        $executable->import();
        $executable->saveMessage('XXX');
      }catch (\Exception $e){
        $migration->setStatus(MigrationInterface::STATUS_IDLE);
        $executable->saveMessage($e->getMessage());
      }

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
