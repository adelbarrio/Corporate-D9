<?php

namespace Drupal\osha_workflow;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\osha_workflow\VwHelper;
use Drupal\osha_workflow\Form\VwApproveForm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;

/**
 * General class for entity hooks.
 */
class VwEntity implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use DependencySerializationTrait;

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
   * The AccountInterface object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public function __construct(BlockManager $block_manager, VwHelper $vasefe_helper, RouteMatchInterface $route_match, FormBuilder $form_builder, ConfigFactoryInterface $config_factory, AccountInterface $account) {
    $this->blockManager = $block_manager;
    $this->helper = $vasefe_helper;
    $this->routeMatch = $route_match;
    $this->formBuilder = $form_builder;
    $this->configFactory = $config_factory;
    $this->account = $account;
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
      $container->get('current_user')
    );
  }

  /**
   * Form alter for for Corporate content types.
   *
   * @see \hook_form_alter()
   */
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) {
    if ($form_id == 'content_moderation_entity_moderation_form') {
      $state = $this->helper->getNodeModerationState();

      // Remove the moderation form if the current user has the role "approver".
      if ($state == 'to_be_approved' && array_search('approver', $this->account->getRoles())) {
       // $form['#access'] = FALSE;
        $form['approving'] = [
          '#type' => 'hidden',
          '#id' => 'approving',
          '#value' => 'approver'
        ];
      }else{
        $form['approving'] = [
          '#type' => 'hidden',
          '#id' => 'approving',
          '#value' => 'other'
        ];
      }

      // Custom validation to control if the list is empty.
      $form['#validate'][] = [$this, 'formValidateAlter'];
    }

    if ($form_id == 'node_article_edit_form' || $form_id == 'node_25th_anniversary_edit_form'||
      $form_id == 'node_calls_edit_form'|| $form_id == 'node_directive_edit_form'|| $form_id == 'node_guideline_edit_form'||
      $form_id == 'node_highlight_edit_form'|| $form_id == 'node_infographic_edit_form'|| $form_id == 'node_job_vacancies_edit_form'||
      $form_id == 'node_news_edit_form'|| $form_id == 'node_press_release_edit_form'|| $form_id == 'node_publication_edit_form'||
      $form_id == 'node_seminar_edit_form') {
      // Load the osha block.
      $plugin_block = $this->blockManager->createInstance('osha_workflow_block', []);
      $osha_block = $plugin_block->build();

      // Set the position of new item on form.
      $osha_block['#weight'] = 99;
      $osha_block['#group'] = 'footer';

      // Set the new item.
      $form['osha_block'] = $osha_block;

      // Custom validation to control if the list is empty.
      $form['#validate'][] = [$this, 'formValidateAlter'];
    }
  }

  /**
   * Adds a new osha Workflow validaton.
   *
   * @see \hook_form_alter()
   */
  public function formValidateAlter(&$form, FormStateInterface $form_state) {
    // Get the new moderation state.
    $moderation_state = ($form_state->hasValue('moderation_state')) ? $form_state->getValue('moderation_state')[0]['value'] : $form_state->getValue('new_state');

    // From draft to final draft.
    if ($moderation_state == 'final_draft') {
      // Get the list of reviewers.
      $list = $this->helper->getModerationList('reviewers');
      if (empty($list)) {
        // Return default reviewers.
        $list = $this->helper->getDefaultList('reviewers');
      }
    }

    // From final draft to be reviewed.
    if ($moderation_state == 'to_be_reviewed') {
      // Get the list of reviewers.
      $list = $this->helper->getModerationList('project_managers');
      if (empty($list)) {
        // Return default approvers.
        $list = $this->helper->getDefaultList('project_managers');
      }
    }

    // From to be reviewd to to be approved
    if ($moderation_state == 'to_be_approved') {
      // Get the list of approvers.
      $list = $this->helper->getModerationList('approvers');
      if (empty($list)) {
        // Return default approvers.
        $list = $this->helper->getDefaultList('approvers');
      }
    }

  }

  /**
   * View alter to add the approver form in latest revision view.
   *
   * @see \hook_entity_view_alter()
   */
  public function viewAlter(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display) {
    // Filter by node type.
    if (!$entity instanceof Node || $this->routeMatch->getRouteName() == 'layout_builder.defaults.node.view') {
      return;
    }

    // Workflow settings of current node.
    $workflow = $this->helper->getWorkflow();

    if (!isset($workflow) || empty($workflow)) {
      return;
    }

    $workflow_settings = $workflow->get('type_settings')['entity_types']['node'];

    // Check if current entity is included in the workflow settings.
    if (!in_array($entity->bundle(), $workflow_settings)) {
      return;
    }

    // Validate if is the full display.
    if ($display->id() !== 'node.' . $entity->bundle() . '.full') {
      return;
    }

    // Determine the list.
    $table = '';
    $config = $this->configFactory->getEditable('osha_workflow.general');
    $lists = $config->get('lists');
    if (array_key_exists('list', $lists)) {
      foreach ($lists['list'] as $list) {
        if ($list['workflow'] == $workflow->id() && $list['workflow_state'] == $this->helper->getNodeModerationState()) {
          $table = $list['name'];
        }
      }
    }

    // Show the approve form if the list exists and the user has access.
    if (!empty($table) && $this->helper->checkUserAccess(strtolower($table))) {
      array_unshift($build, $this->formBuilder->getForm(VwApproveForm::class, $table));
    }

    // Set the block as first element.
    $plugin_block = $this->blockManager->createInstance('osha_workflow_block', []);
    array_unshift($build, $plugin_block->build());

  }

  /**
   * Alter the local tasks.
   *
   * @see \hook_local_tasks_alter()
   */
  public function localTastAlter(&$local_tasks) {

    // Remove un used tab.
    unset($local_tasks['content_moderation.moderated_content']);

    // Get VW default settings.
    $config = $this->configFactory->getEditable('osha_workflow.general');
    $lists = $config->get('lists');
    if (array_key_exists('list', $lists)) {
      foreach ($lists['list'] as $list) {
        $alias = "";
        $name = strtolower($list['name']);
        switch ($name){
          case "reviewers": $alias="RM List";break;
          case "project_managers": $alias="Review";break;
          case "approvers": $alias= "Approve";break;
        }
        // Generate the new local task.
        $local_tasks['entity.node.' . $name . '_node'] = [
          'route_name' => "osha_workflow." . $name . ".list",
          'base_route' => "entity.node.canonical",
          'class' => 'Drupal\Core\Menu\LocalTaskDefault',
          'route_parameters' => [
            'list_name' => $name,
          ],
          'options' => [],
          'title' => $alias,
        ];
      }
     // unset($local_tasks['entity.node.reviewers_node']);
    }

  }

}
