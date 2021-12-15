<?php

namespace Drupal\search_and_replace\Plugin\Scanner;

use Drupal\scanner\Plugin\Scanner\Node;
use Drupal\node\Entity\Node as CoreNode;
use Drupal\scanner\AdminHelper;

/**
 * Class SarNode provides custom search and replace behavior.
 */
class SarNode extends Node {

  /**
   * {@inheritdoc}
   */
  public function search($field, $values) {
    $title_collect = [];
    // $field will be string composed of entity type, bundle name, and field
    // name delimited by ':' characters.
    [$entityType, $bundle, $fieldname] = explode(':', $field);

    $conditionVals = parent::buildCondition($values['search'], $values['mode'], $values['wholeword'], $values['regex'], $values['preceded'], $values['followed']);
    $database = \Drupal::database();
    if ($fieldname=='title'){
      $query = $database->select('node_field_data', 'n');
      $query->fields("n", ['nid', 'langcode', 'status', 'title']);
      $query->addField('n','title','value');
      $query->condition('title', $conditionVals['condition'], $conditionVals['operator']);
      $query->condition('type',$bundle);
      if ($values['published']){
        $query->condition('status',1,'=');
      }
      if ($values['language'] !=='all'){
        $query->condition('langcode',$values['language'],'=');
      }
    }else{
      $fieldnamevalue = $fieldname . "_value";
      $query = $database->select('node__'.$fieldname, 'f');
      $query->join('node_field_data','n','f.entity_id = n.nid and f.langcode=n.langcode');
      $query->fields('n',['nid','title','status']);
      $query->addField('f','langcode');
      $query->addField('f',"$fieldnamevalue",'value');
      $query->condition("$fieldnamevalue", $conditionVals['condition'], $conditionVals['operator']);
      $query->condition('bundle',$bundle);
      if ($values['published']){
        $query->condition('status',1,'=');
      }
      if ($values['language'] !=='all'){
        $query->condition('f.langcode',$values['language'],'=');
      }
    }
    $results = $query->execute();
    foreach ($results as $res){
      // build hint string displayed in results_final form field
      preg_match_all($conditionVals['phpRegex'], $res->value, $matches, PREG_OFFSET_CAPTURE);
      $newValues = [];
      foreach ($matches[0] as $v){
        $start=$v[1];
        if($values['preceded'] !== ''){
          $start -= strlen($values['preceded']);
        }
        $replaced = preg_replace($conditionVals['phpRegex'], "<strong>$v[0]</strong>", preg_split("/\s+/", substr($res->value, $start), 6));
        if (count($replaced) > 1){
          array_pop($replaced);
        }
        $newValues[] = implode(' ', $replaced);
      }
      $uniqueid = $field .":" . $res->nid .":" . $res->langcode;
      $title_collect[$uniqueid]['title'] = $res->title;
      $title_collect[$uniqueid]['lang'] = $res->langcode;
      $title_collect[$uniqueid]['snippet'] = $newValues;
      $title_collect[$uniqueid]['field'] = $fieldname;
      $title_collect[$uniqueid]['nid'] = $res->nid;
      $title_collect[$uniqueid]['uniqueid'] = $uniqueid;
    }
    return $title_collect;
  }

  /**
   * {@inheritdoc}
   */
  public function replace($field, array $values, array $undo_data) {
    // Helper objects to generate a subset of id's to filter
    // (selected items in results_final form)
	  $results_final_field = [];
	  foreach ($values['results_final'] as $vid){
		  if (implode(":",array_slice(explode(":",$vid),0,3))===$field){ $results_final_field[] = $vid;}
	  }
    list($entityType, $bundle, $fieldname) = explode(':', $field);
    $data = $undo_data;
    if (!is_array($data)){
      $data = [];
    }
    $operations= $values['results']['#data']['values'][$entityType][$bundle][$fieldname];
    $conditionVals = parent::buildCondition($values['search'], $values['mode'], $values['wholeword'], $values['regex'], $values['preceded'], $values['followed']);
    foreach($operations as $k => $v){
      if (!in_array($k,$results_final_field)){
        continue;
      }
	    list(, , $fieldname, $nid, $langcode) = explode(":", $k);
	    $node = CoreNode::load($nid);
	    $node = $node->getTranslation($langcode);
	    $nodeField = $node->get($fieldname);
	    $fieldType = $nodeField->getFieldDefinition()->getType();
	    if (in_array($fieldType, ['text_with_summary', 'text', 'text_long'])){
	      $fieldValue = $nodeField->getValue()[0];
          $fieldValue['value'] = preg_replace($conditionVals['phpRegex'], $values['replace'], $fieldValue['value']);
	    }
	    elseif ($fieldType == 'string'){
        $fieldValue = preg_replace($conditionVals['phpRegex'], $values['replace'], $nodeField->getString());
	    }
      $node->$fieldname = $fieldValue;
      $data["node:$nid"]['old_vid'] = $node->vid->getString();
      $node->setNewRevision(TRUE);
      $node->revison_log = $this->t(
        'Replaced %search with %replace via OSHA Serch and Replace module.',
        ['%search' => $values['search'], '%replace' => $values['replace']]
      );
	    $node->save();
	    $data["node:$nid"]['new_vid'] = $node->vid->getString();
    }

    return $data;
  }
  /**
   * {@inheritdoc}
   */
  public function undo(array $data) {
    $revision = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($data['old_vid']);
    $revision->setNewRevision(TRUE);
    $revision->revision_log = $this->t('Copy of the revision from %date via Search and Replace Undo', ['%date' => \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime())]);
    $revision->isDefaultRevision(TRUE);
    $revision->save();
  }
}
