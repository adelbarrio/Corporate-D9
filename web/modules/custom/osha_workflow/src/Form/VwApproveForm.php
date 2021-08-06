<?php

namespace Drupal\osha_workflow\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\osha_workflow\VwHelper;

/**
 * General class for Vw approve form.
 */
class VwApproveForm extends FormBase {

  /**
   * The osha helper service.
   *
   * @var \Drupal\osha_workflow\VwHelper
   */
  protected $helper;

  /**
   * The Config Factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(VwHelper $vasefe_helper, ConfigFactoryInterface $config_factory) {
    $this->helper = $vasefe_helper;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('osha_workflow.helper'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'approve_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $table = NULL) {
    // Set the table name.
    $form_state->set('osha_workflow_table', strtolower($table));

    // Store the list configration.
    $osha_config = $this->configFactory->getEditable('osha_workflow.general');
    foreach ($osha_config->get('lists')['list'] as $list) {
      if ($list['name'] == ucfirst($form_state->get('osha_workflow_table'))) {
        $form_state->set('osha_workflow_list_configuration', $list);
      }
    }

    $list_state = $form_state->get('osha_workflow_list_configuration')['workflow_state'];
    $list_previous_state = $form_state->get('osha_workflow_list_configuration')['workflow_state_previous'];

    // Show the form if the node contains the correct moderation state.
    if ($this->helper->getNodeModerationState() !== $list_state) {
      return $form;
    }

    // Get the list of states of current bundle.
    $workflow_settings = $this->helper->getWorkFlowStates();

    if (isset($workflow_settings)) {
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Approve'),
      ];

      $form['reject'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reject, send back to @state', ['@state' => $workflow_settings[$list_previous_state]['label']]),
        '#submit' => [[$this, 'submitReject']],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update the status of current user.
    $this->helper->approveUser($form_state->get('osha_workflow_table'));


    // If all users approved the content, then set the node as the.
    // next state defined in the list.
    if ($this->helper->getModerationListStatus($form_state->get('osha_workflow_table'))) {
      $this->helper->setNodeModerationState($form_state->get('osha_workflow_list_configuration')['workflow_state_next']);
    }
    else {
      // Auto save the current node to run the email handle.
      $node = $this->helper->getLastRevisionNode();
      $node->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitReject(array &$form, FormStateInterface $form_state) {
    // Set the previous state.
    $this->helper->setNodeModerationState($form_state->get('osha_workflow_list_configuration')['workflow_state_previous']);

    // Reset the current users approver status.
    $this->helper->resetUsersStatus($form_state->get('osha_workflow_table'));
  }

}
