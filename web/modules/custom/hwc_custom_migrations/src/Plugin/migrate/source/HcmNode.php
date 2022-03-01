<?php

namespace Drupal\hwc_custom_migrations\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\node\Plugin\migrate\source\d7\Node;

/**
 * Drupal 7 node source from database.
 *
 * @MigrateSource(
 *   id = "hcw_d7_node"
 * )
 */
class HcmNode extends Node {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (!parent::prepareRow($row)) {
      return FALSE;
    }

    // Get the WAN control access.
    $workbench_access = $this->select('workbench_access_node', 'wan')
      ->fields('wan', ['access_id'])
      ->condition('nid', $row->getSourceProperty('nid'))
      ->execute()
      ->fetchCol();
    if (!empty($workbench_access[0])){
      if ($workbench_access[0] !== 'section') {
        $row->setSourceProperty('workbench_access', [0 => ['tid' => $workbench_access[0]]]);
      }
    }

    // If the item is published, we should set the content moderation state to
    // active.
    if ($row->get('status') == 1) {
      $state = 'published';
    }
    else {
      $state = 'draft';
    }

    // Include path alias.
    $nid = $row->getSourceProperty('nid');
    $query = $this->select('url_alias', 'ua')
      ->fields('ua', ['alias']);
    $query->condition('ua.source', 'node/' . $nid);
    $alias = $query->execute()->fetchField();
    if (!empty($alias)) {
      $row->setSourceProperty('alias', '/' . $alias);
    }

    // Set the Moderation State on the source for processing.
    $row->setSourceProperty('moderation_state', $state);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    $fields['workbench_access'] = $this->t('The workbench access field for this node.');
    return $fields;
  }

}
