<?php

namespace Drupal\osha_workflow;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Database\Connection;
use Drupal\content_moderation\ModerationInformation;
use Drupal\node\Entity\Node;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * General service for moderation-related queries to the database.
 */
class VwHelper {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The AccountInterface object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The database manager.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The Request object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The moderation manager.
   *
   * @var \Drupal\content_moderation\ModerationInformation
   */
  protected $moderationManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountProxy $account, Connection $database, RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, ModerationInformation $moderation_information) {
    $this->account = $account;
    $this->database = $database;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->moderationManager = $moderation_information;
  }

  /**
   * Gets the las revision node.
   *
   * @return array|\Drupal\node\Entity\Node
   *   The node if exists.
   */
  public function getLastRevisionNode() {
    $node = $this->routeMatch->getParameter('node');

    if ($node instanceof Node) {
      /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $storage */
      $storage = $this->entityTypeManager->getStorage('node');
      $revision = $storage->getLatestRevisionId($node->id());
      return $storage->loadRevision($revision);
    }else{
      $vid = $this->routeMatch->getParameter('node_revision');
      $storage = $this->entityTypeManager->getStorage('node');
      $revision = ($vid !=null)? $storage->loadRevision($vid): [];
      return $revision;
    }

    return [];
  }

  public function getRoute(){
    return $this->routeMatch;
  }
  /**
   * Get the moderation state of current node.
   *
   * @return string
   *   The moderation state of current node.
   */
  public function getNodeModerationState() {
    $node = $this->getLastRevisionNode();
    if (empty($node)) {
      return '';
    }

    $moderation_state = $node->get('moderation_state')->getValue();
    if (empty($moderation_state)) {
      return NULL;
    }

    return $node->get('moderation_state')->getValue()[0]['value'];
  }

  /**
   * Set the new state to the current node.
   *
   * @param string $state
   *   The state name.
   */
  public function setNodeModerationState($state) {
    $this->getLastRevisionNode()->set('moderation_state', $state)->save();
  }

  /**
   * Check if any user of the list has not approved it.
   *
   * @param string $table
   *   The table name.
   *
   * @return bool
   *   If one of the users has not approved it
   */
  public function getModerationListStatus($table) {
    // Get complete moderation list.
    $results = $this->getModerationList($table);

    foreach ($results as $result) {
      if ($result->status == $this->t('Waiting to approve')) {
        // Return FALSE if any user has not approved it.
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Gets the moderation list.
   *
   * @param string $table
   *   The table name.
   */
  public function getModerationList($table) {
    try {
      $query = $this->database->select("osha_workflow_$table", 'v')
        ->condition('node_id', $this->getLastRevisionNode()->id(), '=')
        ->fields('v', ['id', 'node_id', 'user_id', 'status', 'weight']);
      $query->orderBy('v.weight');

      return $query->execute()->fetchAll();
    } catch (\Exception $e) {
    }
  }

  /**
   * Gets the default moderation list.
   *
   * @param string $table
   *   The table name.
   */
  public function getDefaultList($table){

    $entity = $this->getLastRevisionNode();

    $userRole = "";
    $condition = "sa.entity_id = :entity_id";
    switch($table){
      case "approvers":
        $userRole ="approver";
        $condition = "sa.entity_id=13 OR sa.entity_id = :entity_id";
        break;
      case "reviewers": $userRole ="review_manager"; break;
      case "project_managers": $userRole ="project_manager"; break;
    }

    // Get the section of the node.
    if($entity->hasField('field_section') && $entity->get('field_section')->get(0) != null){
      $nodeSection = $entity->get('field_section')->get(0)->getValue();
    }
    else{
      $nodeSection = null;
    }

    if( !$nodeSection || is_null($nodeSection) || empty($nodeSection) ){
      $data['to'] = [];
      return \Drupal::service('class_resolver')
        ->getInstanceFromDefinition(VwCmn::class)
        ->mailDataAlter($entity, $data);
    }

    $nodeSectionId = $nodeSection['target_id'];
    if(!$nodeSectionId || empty($nodeSectionId)){
      $data['to'] = [];
      return \Drupal::service('class_resolver')
        ->getInstanceFromDefinition(VwCmn::class)
        ->mailDataAlter($entity, $data);
    }

    // Get the section ids with the corresponding section of the node.
    $querySaResult = $this->database->query(
      'SELECT e.id
            FROM section_association e
            WHERE e.section_id = :section_id', array(':section_id' => $nodeSectionId));
    $entityId = "";
    foreach ($querySaResult as $item) {
      $entityId= $item->id;
    }

    // Get the user ids from the section_association__user_id with the corresponding section id.
    $queryUserSectionResult = $this->database->query(
      'SELECT sa.user_id_target_id
            FROM section_association__user_id sa
            WHERE '.$condition.' ', array(":entity_id" => $entityId));
    $sectionUserTargetIds = [];
    $weight = 0;
    $query = \Drupal::entityQuery('user');
    $uids = $query->execute();

    foreach ($queryUserSectionResult as $item) {
      if(in_array($item->user_id_target_id, $uids)) {
        $approver = $this->entityTypeManager->getStorage('user')
          ->load($item->user_id_target_id);
        if (in_array($userRole, $approver->getRoles()) && $approver->isActive()) {
          array_push($sectionUserTargetIds, [
            'node_id' => $entity->id(),
            'user_id' => $item->user_id_target_id,
            'status' => $this->t('Waiting to approve'),
            'weight' => $weight
          ]);
          $weight++;
        }
      }
    }

    try {
      if($userRole == 'approver') {
        // MDR-4761 - William only approver for articles and press releases
        if ($entity->bundle() == 'article' || $entity->bundle() == 'press_release') {
          $query = $this->database
            ->insert('osha_workflow_' . $table)
            ->fields(['node_id', 'user_id', 'status', 'weight']);
          $query->values([
            $entity->id(),
            312,
            $this->t('Waiting to approve'),
            0
          ]);
          $query->values([
            $entity->id(),
            313,
            $this->t('Waiting to approve'),
            1
          ]);
          $query->execute();
        }
        else {
          $query = $this->database
            ->insert('osha_workflow_' . $table)
            ->fields(['node_id', 'user_id', 'status', 'weight']);
            $query->values([
              $entity->id(),
              312,
              $this->t('Waiting to approve'),
              0
            ]);
          $query->execute();
        }
      }
      else{
        // Normal flow
        $query = $this->database
          ->insert('osha_workflow_'.$table)
          ->fields(['node_id', 'user_id', 'status', 'weight']);
        foreach ($sectionUserTargetIds as $record){
          $query->values($record);
        }
        $query->execute();
      }

    }catch (\Exception $e){

    }
  }

  /**
   * Set the status to approve of users list.
   *
   * @param string $table
   *   The table name.
   */
  public function resetUsersStatus($table) {
    $this->database->update("osha_workflow_$table")
      ->fields([
        'status' => $this->t('Waiting to approve'),
      ])
      ->condition('node_id', $this->getLastRevisionNode()->id())->execute();
  }

  /**
   * Add a user in the list.
   *
   * @param string $table
   *   The table name.
   * @param array $fields
   *   The array with fields.
   */
  public function addUserToList($table, array $fields) {
    $this->database->insert("osha_workflow_$table")
      ->fields($fields)
      ->execute();
  }

  /**
   * Delete a user in the list.
   *
   * @param string $table
   *   The table name.
   * @param array $fields
   *   The array with fields.
   */
  public function deleteUserToList($table, array $fields) {
    $this->database->delete("osha_workflow_$table")
      ->condition('node_id', $fields['node_id'])
      ->condition('user_id', $fields['user_id'])
      ->execute();
  }

  public function setUserWeight($table, array $fields) {
    $this->database->update("osha_workflow_$table")
      ->fields([
        'weight' => $fields['weight'],
      ])
      ->condition('node_id', $fields['node_id'])
      ->condition('user_id', $fields['user_id'])->execute();
  }

  /**
   * Set the status to approve of user list.
   *
   * @param string $table
   *   The table name.
   */
  public function approveUser($table) {
    // Update the status of current user.
    $this->database->update("osha_workflow_$table")
      ->fields([
        'status' => $this->t('Approved'),
      ])
      ->condition('node_id', $this->getLastRevisionNode()->id())
      ->condition('user_id', $this->account->id())->execute();
  }

  /**
   * Gets the next user of list.
   *
   * @param string $table
   *   The table name.
   *
   * @return array|\Drupal\user\Entity\User
   *   The next user.
   */
  public function getNextUser($table) {
    $users = $this->getModerationList($table);
    if ($users) {
      foreach ($users as $i => $user) {
        if ($user->user_id == $this->account->id()) {
          return $this->entityTypeManager->getStorage('user')->load($users[($i + 1)]->user_id);
        }
      }
    }

    return [];
  }

  /**
   * Gets the previous user of list.
   *
   * @param string $table
   *   The table name.
   *
   * @return array|\Drupal\user\Entity\User
   *   The next user.
   */
  public function getLastUserList($table) {
    $users = $this->getModerationList($table);
    if (!empty($users)) {
      return $users[count($users) - 1];
    }

    return [];
  }

  /**
   * Check if the user status.
   *
   * @param string $table
   *   The table name.
   *
   * @return bool
   *   If the user has access.
   */
  public function checkUserAccess($table) {
    $user_list = [];
    $results = $this->getModerationList($table);
    foreach ($results as $data) {
      if ($data->status == $this->t('Waiting to approve')) {
        $user_list[] = $data->user_id;
      }
    }

    if($table=="reviewers"){return FALSE;}

    if (isset($user_list[0]) && $user_list[0] == $this->account->id()) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Check if the user is included to the list.
   *
   * @param string $table
   *   The table name.
   * @param string $user_id
   *   The user id.
   *
   * @return bool
   *   If the user has access.
   */
  public function checkUserExists($table, $user_id) {
    $results = $this->getModerationList($table);
    foreach ($results as $data) {
      if ($data->user_id == $user_id) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Get the workflow of current node.
   *
   * @return array|\Drupal\workflows\Entity\Workflow
   *   If the user has access.
   */
  public function getWorkflow() {
    $node = $this->getLastRevisionNode();

    if (!isset($node)) {
      return [];
    }
    if (is_array($node)) {
      $node = reset($node);
      if (!$node) {
        return [];
      }
    }

    /** @var \Drupal\workflows\Entity\Workflow $flow */
    return $this->moderationManager->getWorkflowForEntityTypeAndBundle('node', $node->bundle());
  }

  /**
   * Get the workflow states.
   *
   * @return array
   *   The array with states.
   */
  public function getWorkFlowStates() {
    /** @var \Drupal\workflows\Entity\Workflow $flow */
    $flow = $this->moderationManager->getWorkflowForEntityTypeAndBundle('node', $this->getLastRevisionNode()->bundle());
    return $flow->get('type_settings')['states'];
  }

}
