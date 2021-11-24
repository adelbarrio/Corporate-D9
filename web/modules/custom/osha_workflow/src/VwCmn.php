<?php

namespace Drupal\osha_workflow;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\osha_workflow\VwHelper;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * General class for Content moderatoin notification hooks.
 */
class VwCmn implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * The osha helper service.
   *
   * @var \Drupal\osha_workflow\VwHelper
   */
  protected $helper;

  /**
   * The Request object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The form builder manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $formBuilder;

  /**
   * The Config Factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(BlockManager $block_manager, VwHelper $osha_helper, RouteMatchInterface $route_match, FormBuilder $form_builder, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->blockManager = $block_manager;
    $this->helper = $osha_helper;
    $this->routeMatch = $route_match;
    $this->formBuilder = $form_builder;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('osha_workflow.helper'),
      $container->get('current_route_match'),
      $container->get('form_builder'),
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Hook bridge.
   *
   * @see \hook_content_moderation_notification_mail_data_alter()
   */
  public function mailDataAlter(EntityInterface $entity, array &$data) {
    // Workflow settings of current node.
    $this->filterUsers($entity, $data);
    $workflow = $this->helper->getWorkflow();
    if (!$workflow){
      $workflow = $entity;
    }

    // Determine the list.
    $table = '';
    $config = $this->configFactory->getEditable('osha_workflow.general');
    $lists = $config->get('lists');
    if (array_key_exists('list', $lists)) {
      foreach ($lists['list'] as $list) {
        if ($list['workflow'] == $workflow->id() &&
          $list['workflow_state'] == $this->helper->getNodeModerationState()) {
          $table = strtolower($list['name']);
        }
      }
    }

    /** @var \Drupal\content_moderation_notifications\Entity\ContentModerationNotification $notification */
    $notification = $data['notification'];

    // Alter the notification to send the email only to the first user.
    if ($notification->getOriginalId() == 'draft_to_final_draft') {
      $users = $this->helper->getModerationList($table);
      $users = (array) $users;
      /** @var \Drupal\user\Entity\User $user */
        unset($data['to']);
        $data['to'] = [];
      for ($i=0; $i< count($users); $i++ ) {
        $user = $this->entityTypeManager->getStorage('user')->load($users[$i]->user_id);
        if($user->isActive() && $user->hasRole('review_manager')){
          array_push($data['to'], $user->getEmail());
        }
      }
      $this->helper->resetUsersStatus('reviewers');
    }

    // Alter the notification to filter users.
    if ($notification->getOriginalId() == 'to_approved_to_be_reviewed') {
      $users = $this->helper->getModerationList($table);
      $users = (array) $users;
      /** @var \Drupal\user\Entity\User $user */
      unset($data['to']);
      $data['to'] = [];
      for ($i=0; $i< count($users); $i++ ) {
        $user = $this->entityTypeManager->getStorage('user')->load($users[$i]->user_id);
        if($user->isActive() && $user->hasRole('project_manager')){
          array_push($data['to'], $user->getEmail());
        }
      }
      $this->helper->resetUsersStatus('project_managers');
    }


    // Alter the notification to send the email only to the first user.
    if ($notification->getOriginalId() == 'from_any_status_to_to_be_approved') {
      $users = $this->helper->getModerationList($table);
      $users = (array) $users;
      /** @var \Drupal\user\Entity\User $user */
      $user = $this->entityTypeManager->getStorage('user')->load($users[0]->user_id);
      $data['to'] = [
        0 => $user->getEmail(),
      ];
      $this->helper->resetUsersStatus('approvers');

    }

    // Alter the notification to send the email to the next user of.
    // list if exists.
    if ($notification->getOriginalId() == 'to_next_approver') {
      $next_user = $this->helper->getNextUser($table);
      if (!empty($next_user)) {
        $data['to'] = [
          0 => $next_user->getEmail(),
        ];
      }
    }
  }

  public function filterUsers(EntityInterface $entity, array &$data){

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
      $querySaResult = \Drupal::database()
        ->query('SELECT e.id
            FROM section_association e
            WHERE e.section_id = :section_id', array(
          ':section_id' => $nodeSectionId,
        ));
      $entityId = "";
      foreach ($querySaResult as $item) {
        $entityId= $item->id;
      }

      // Get the user ids from the section_association__user_id with the corresponding section id.
      $queryUserSectionResult = \Drupal::database()
        ->query('SELECT sa.user_id_target_id
            FROM section_association__user_id sa
            WHERE sa.entity_id = :entity_id', array(
          ":entity_id" => $entityId,
        ));
      $sectionUserTargetIds = [];
      foreach ($queryUserSectionResult as $item) {
        array_push($sectionUserTargetIds,$item->user_id_target_id);
      }

      // Get the users from mail from data['to'].
      $data_ids =[];
      foreach ($data['to'] as $receivedMail) {
        if(user_load_by_mail($receivedMail) instanceof \Drupal\ds\Plugin\DsField\User\User){
          array_push($data_ids);
        }
      }

      // Compare users from data['to']  with  users that belong to the section of the node.
      $sectionBelongingUsers = array_diff($sectionUserTargetIds,$data_ids);
   //   $allowedRoles = ['project_manager'];

      // Set data['to'] to the section Belonging users
      unset($data['to']);
      $data['to'] = [];
      foreach ($sectionBelongingUsers as $item) {
        $theUser = \Drupal::entityTypeManager()->getStorage('user')->load($item);
        // @todo - Check if this is correct.
        if (!is_null($theUser)) {
          if ($theUser->isActive()) {
            //  if(count(array_intersect(($theUser->getRoles()), $allowedRoles)) > 0){
            array_push($data['to'], $theUser->mail->value);
            //   }
          }
        }
      }
  }

}
