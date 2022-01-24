<?php


namespace Drupal\osha_unpublish;


use Drupal\Core\Entity\EntityTypeManager;

class OldNodesService {

  protected $entityTypeManager;

  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  public function load() {
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->condition('type', 'news')
      ->condition('field_avoid_archived', 0, '=')
      ->condition('field_publication_date', date('Y-m-d h:i:s', strtotime('-1 year')), '<');
    $nids = $query->execute();
    return $storage->loadMultiple($nids);
  }

}
