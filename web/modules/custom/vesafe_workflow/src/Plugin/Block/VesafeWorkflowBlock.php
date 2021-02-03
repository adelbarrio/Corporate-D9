<?php

namespace Drupal\vesafe_workflow\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\vesafe_workflow\VesafeWorkFlowHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\SortArray;

/**
 * Provides a 'Vesafe Workflow' Block.
 *
 * @Block(
 *   id = "vesafe_workflow_block",
 *   admin_label = @Translation("Vesafe Workflow"),
 * )
 */
class VesafeWorkflowBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Vesafe helper service.
   *
   * @var \Drupal\vesafe_workflow\VesafeWorkFlowHelper
   */
  protected $helper;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, VesafeWorkFlowHelper $vasefe_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->helper = $vasefe_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('vesafe_workflow.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (!\Drupal::currentUser()->hasPermission('view any unpublished content')) {
      return;
    }
    $output = [
      '#type' => 'container',
      'content' => [],
      '#attributes' => ['class' => 'vesafe-workflow-list-widget'],
    ];

    // Get the current node object.
    $node_state = $this->helper->getNodeModerationState();

    // Get the list of states of current bundle.
    $workflow_settings = $this->helper->getWorkFlowStates();

    if (isset($workflow_settings)) {
      // Sort states by weight.
      uasort($workflow_settings, [SortArray::class, 'sortByWeightElement']);

      // Set the items by state.
      $i = 0;
      foreach ($workflow_settings as $name => $state) {
        // Default attributes.
        $label = $state['label'];
        $item_class = '';

        // Set the active item.
        if ($node_state == $name) {
          $item_class = ' active';
        }

        // Set the first item.
        if ($i == 0) {
          $item_class = 'first';
        }

        // Set the last item.
        if ($i == (count($workflow_settings) -1)) {
          $item_class = 'last';
        }

        // Generate the item.
        $output['content'][] = [
          '#markup' => "<p class='$item_class'>$label</p>",
        ];

        $i++;
      }
    }

    return $output;
  }
}
