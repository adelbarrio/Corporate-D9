<?php

namespace Drupal\osha_workflow\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\osha_workflow\VwHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\SortArray;

/**
 * Provides a 'osha Workflow' Block.
 *
 * @Block(
 *   id = "osha_workflow_block",
 *   admin_label = @Translation("osha Workflow"),
 * )
 */
class VwBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The osha helper service.
   *
   * @var \Drupal\osha_workflow\VwHelper
   */
  protected $helper;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, VwHelper $vasefe_helper) {
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
      $container->get('osha_workflow.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (!\Drupal::currentUser()->hasPermission('view any unpublished content')) {
      return [];
    }
    $output = [
      '#type' => 'container',
      'content' => [],
      '#attributes' => ['class' => ['osha-workflow-list-widget']],
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

        // Set the first item.
        if ($i == 0) {
          $item_class = 'first';
        }

        // Set the last item.
        if ($i == (count($workflow_settings) - 1)) {
          $item_class = 'last';
        }

        // Set the active item.
        if ($node_state == $name) {
          $item_class .= ' active';
        }

        // Generate the item.
        $output['content'][] = [
          '#markup' => "<p class='$item_class'>$label</p>",
        ];

        $i++;
      }
    }

    $output['#attached']['library'][] = 'osha_workflow/osha_workflow.block';

    return $output;
  }

  // Ribbon cache fix?
  /**
   * @return int
   */
  public function getCacheMaxAge() {
    return 0;
  }
}
