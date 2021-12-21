<?php
namespace Drupal\osha_lingua_tools;

/**
 * Default controller for theosha_lingua_tools module.
 */
class DefaultController extends ControllerBase {

  public function osha_lingua_clone_access(\Drupal\node\NodeInterface $source_node, Drupal\Core\Session\AccountInterface $account) {
    $user = \Drupal::currentUser();
    if (user_access('edit any dangerous_substances content', $user) && ($source_node->getType() == 'dangerous_substances')) {
      return TRUE;
    }
    if (user_access('edit any musculoskeletal_disorders content', $user) && ($source_node->getType() == 'musculoskeletal_disorders')) {
      return TRUE;
    }
    return FALSE;
  }

  public function osha_lingua_clone_node(\Drupal\node\NodeInterface $source_node) {
    $user = \Drupal::currentUser();

    $node = new stdClass();
    $node->title = 'Copy of - ' . $source_node->getTitle();
    $node->type = $source_node->getType();
    node_object_prepare($node);

    $node->language = $source_node->language;
    $node->uid = $user->uid;
    $node->status = $source_node->isPublished();
    $node->promote = $source_node->promote;

    $fields = field_info_instances('node', $source_node->getType());
    foreach ($fields as $field) {
      if (in_array($field['field_name'], [
        'field_file',
        'field_publication_related_res',
        'field_related_publications',
      ])) {
        continue;
      }
      if (@$source_node->{$field['field_name']}[$source_node->language]) {
        $node->{$field['field_name']}[$source_node->language] = $source_node->{$field['field_name']}[$source_node->language];
      }
      if (@$source_node->{$field['field_name']}[\Drupal\Core\Language\Language::LANGCODE_NOT_SPECIFIED]) {
        $node->{$field['field_name']}[\Drupal\Core\Language\Language::LANGCODE_NOT_SPECIFIED] = $source_node->{$field['field_name']}[\Drupal\Core\Language\Language::LANGCODE_NOT_SPECIFIED];
      }
    }
    $node->title_field[$node->language][0]['value'] = $source_node->getTitle();
    $node->workbench_access = $source_node->workbench_access;
    $node = node_submit($node);
    node_save($node);
    drupal_goto('node/' . $node->nid . '/edit');
  }

}
