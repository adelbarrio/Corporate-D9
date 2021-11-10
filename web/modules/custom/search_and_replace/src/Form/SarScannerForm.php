<?php

namespace Drupal\search_and_replace\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\scanner\Form\ScannerForm;
use Drupal\scanner\Plugin\ScannerPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Render\FormattableMarkup;

class SarScannerForm extends ScannerForm {

  use StringTranslationTrait;

  /**
   * The entity_type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  public function __construct(PrivateTempStoreFactory $tempStore, ScannerPluginManager $scannerManager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($tempStore, $scannerManager);
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('plugin.manager.scanner'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $build = parent::buildForm($form, $form_state);

    /*$build['options']['bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Bundles'),
      '#options' => $this->getAvailableEntityTypes(),
      '#weight' => -2,
      '#description' => $this->t('If none selected, all bundles will be used in search.'),
    ];*/

    /*$build['options']['version'] = [
      '#type' => 'radios',
      '#title' => $this->t('Bundles'),
      '#options' => [0 => $this->t('Published'), 1 => $this->t('Current Draft')],
      '#weight' => -1,
      '#default_value' => 0,
      '#description' => $this->t('Select the versions of the nodes on which you want to perform the search.'),
    ];*/

    $scannerStore = $this->tempStore->get('scanner');
    if (empty($form_state->getValues())) {
      unset($build['replace']);
      unset($build['submit_replace']);
    } else {
      $build['options']['#collapsible'] = TRUE;
      $build['options']['#open'] = FALSE;
      $build['submit_replace']['#validate'] = [$this, '::validateReplace'];
    }

    $build['replace']['#weight'] = 98;
    $build['submit_replace']['#weight'] = 99;
    unset($build['results']);

    $header = [
      'title' => $this->t('Title'),
      'type' => $this->t('Type'),
      'snippet' => $this->t('Snippet'),
      'count' => $this->t('Count'),
      'lang' => $this->t('Language'),
    ];
    $rows = [];

    $results = $scannerStore->get('results');

    if (!empty($results)) {
      foreach ($results['#data']['values']['node'] as $type => $nodes) {
        foreach ($results['#data']['values']['node'][$type] as $field_name => $field_values) {
          foreach ($field_values as $values) {
            $title = $values['title'];
            $row = [
              'title' => $title,
              'type' => $type,
              'count' => count($values['field']),
              'lang' => $values['lang']
            ];
            $snippet = [
              '#markup' => '',
            ];
            $output = '';
            foreach ($values['field'] as $value) {
              $output .= '<div>';
              $output .= '<span class="search-and-replace-info">[One or more matches in <strong>' . $field_name . '</strong>:]</span><br />';
              $output .= '<span class="search-and-replace-text">...' . strip_tags($value, '<strong>') . '...</span>';
              $output .= '</div>';
            }
            $row['snippet'] = new FormattableMarkup($output, []);
            $rows[] = $row;
          }
        }
      }

      $build['results_final'] = [
        '#header' => $header,
        '#type' => 'tableselect',
        '#options' => $rows,
        '#attributes' => array('id' => 'search-and-replace-results'),
        '#weight' => 100,
      ];

      $build['results_final_legend'] = [
        '#markup' => '<span class="legend-span node-in-translation"></span> In active translation job',
      ];
    }

    return $build;
  }

  public function validateReplace(&$form, FormStateInterface $form_state) {
    $scannerStore = $this->tempStore->get('scanner');
    $results = $scannerStore->get('results');
    $to_replace = array_filter($form_state->getValues()['results_final']);
    if (empty($to_replace)) {
      $form_state->setErrorByName('ALL', 'Perform a search first and select at least 1 node.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $scannerStore = $this->tempStore->get('scanner');
    $op = $form_state->getUserInput()['op'];

    // Save the $form_state values into the user tempstore for later.
    foreach ($form_state->getValues() as $key => $value) {
      $scannerStore->set($key, $form_state->getValue($key));
    }

    $scannerStore->set('op', $op);

    if ($op == t('Search')) {
      $fields = \Drupal::config('scanner.admin_settings')->get('fields_of_selected_content_type');

      // Build an array of batch operation jobs.
      // Batch job will need the field and the $form_state values.
      $operations = [];
      foreach ($fields as $key => $field) {
        $operations[] = ['\Drupal\scanner\Form\ScannerForm::batchSearch', [$field, $form_state->getValues()]];
      }

      $batch = [
        'title' => t('Scanner Search Batch'),
        'operations' => $operations,
        'finished' => '\Drupal\scanner\Form\ScannerForm::batchFinished',
        'progress_message' => t('Processed @current out of @total'),
      ];
      batch_set($batch);
      $form_state->setRebuild(TRUE);
    }
    elseif ($op == t('Replace')) {
      // Redirect to the confirmation form.
      $form_state->setRedirect('scanner.admin_confirm');
    }
  }

  /**
   * Gets a list of available entity types as input options.
   *
   * @return array
   *   An array containing the entity type options.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getAvailableEntityTypes() {
    $options = [];

    $bundles = $this->entityTypeBundleInfo->getBundleInfo('node');
    foreach ($bundles as $bundle_id => $bundle) {
      $options[$bundle_id] = $bundle['label'];
    }

    return $options;
  }

}
