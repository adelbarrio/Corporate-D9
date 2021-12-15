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

/**
 * Form for performing searching.
 */
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
    $build['search']['#required'] = TRUE;
    $scannerStore = $this->tempStore->get('scanner');
    if (empty($form_state->getValues())) {
      unset($build['replace']);
      unset($build['submit_replace']);
    }
    else {
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
      'field' => 'field',
      'snippet' => $this->t('Snippet'),
      'count' => $this->t('Count'),
      'lang' => $this->t('Language'),
      'nid' => 'Nid',
      //'uniqueid' => 'uniqueid',
    ];
    $rows = [];

    $results = $scannerStore->get('results');
    if (!empty($results)) {
      foreach ($results['#data']['values']['node'] as $type => $nodes) {
        foreach ($results['#data']['values']['node'][$type] as $field_name => $field_values) {
          foreach ($field_values as $values) {
            $title = $values['title'];
	          $uniqueid = $values['uniqueid'];
            $row = [
              'title' => $title,
              'type' => $type,
	            'field' => $values['field'],
              'count' => count($values['snippet']),
              'lang' => $values['lang'],
	            'nid' => $values['nid'],
	            //'uniqueid' => $values['uniqueid'],
            ];
            $output = '';
            foreach ($values['snippet'] as $k=>$value) {
              if($k==0){$prefix ="";}else{$prefix="<hr>";}
              $output.=$prefix;
              $output .= '<span class="search-and-replace-text">...' . strip_tags($value, '<strong>') . '...</span>';
            }
            $row['snippet'] = new FormattableMarkup($output, []);
            $rows[$uniqueid] = $row;
          }
        }
      }
      $build['results_final'] = [
        '#header' => $header,
        '#type' => 'tableselect',
        '#options' => $rows,
        '#attributes' => ['id' => 'search-and-replace-results'],
        '#weight' => 100,
      ];
      $build['results_final_legend'] = [
        '#markup' => '<span class="legend-span node-in-translation"></span> In active translation job',
      ];
    }
    return $build;
  }

  /**
   * Check if there are any selected operations.
   *
   * Multivalue form field checked as string to detect it's set,
   * as opposed to integer values (not set).
   */
  public function validateReplace(&$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues()['results_final'] as $val) {
      if (gettype($val) == 'string') {
        break;
      }
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

    if ($op == $this->t('Search')) {
	    $scannerStore->set('results',NULL);
	    $scannerStore->set('results_final',NULL);
      $fields = \Drupal::config('scanner.admin_settings')->get('fields_of_selected_content_type');

      // Build an array of batch operation jobs.
      // Batch job will need the field and the $form_state values.
      $operations = [];
      foreach ($fields as $key => $field) {
        $operations[] = [
          '\Drupal\search_and_replace\Form\SarScannerForm::batchSearch',
              [$field, $form_state->getValues()],
        ];
      }
      $batch = [
        'title' => $this->t('Scanner Search Batch'),
        'operations' => $operations,
        'finished' => '\Drupal\search_and_replace\Form\SarScannerForm::batchFinished',
        'progress_message' => $this->t('Processed @current out of @total'),
      ];
      batch_set($batch);
      $form_state->setRebuild(TRUE);
    }
    elseif ($op == $this->t('Replace')) {
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
  /**
   * Batch operation function.
   *
   * @param string $field
   *   The name of the field.
   * @param array $values
   *   The $form_state values.
   * @param array $context
   *   An array containin data that is persisted across batch jobs.
   *
   * @see https://api.drupal.org/api/drupal/core%21includes%21form.inc/group/batch/8.5.x
   */
  public static function batchSearch($field, array $values, array &$context) {
    $pluginManager = \Drupal::service('plugin.manager.scanner');
    list($entityType, $bundle, $fieldname) = explode(':', $field);

    // Attempt to load the plugin.
    try {
      $plugin = $pluginManager->createInstance('scanner_entity');
    }
    catch (PluginException $e) {
      // The instance could not be found so fail gracefully and let the user
      // know.
      \Drupal::logger('scanner')->error($e->getMessage());
      \Drupal::messenger()->addError(t('An error occured @e:', ['@e' => $e->getMessage()]));
    }

    $results = $plugin->search($field, $values);
    if (!empty($results)) {
      $context['results'][$entityType][$bundle][$fieldname] = $results;
      // Number of entities with search term.
      $context['results']['count']['entities'] += count($results);
      foreach ($results as $data) {
        // Number of matches within each field of each entity.
        $context['results']['count']['matches'] += count($data['field']);
        $context['results']['count']['summary']["entities"]["$entityType:".$data["nid"]] =  1;
        $context['results']['count']['summary']["languages"][$data["lang"]] = 1;
        $context['results']['count']['summary']["fields"][$data["field"]] = 1;
        $context['results']['count']['summary']['total'] = $context['results']['count']['summary']['total'] + count($data['snippet']) ;
      }
      $context['message'] = 'Searching through field...';
    }
  }
    /**
   * The batch process has finished.
   *
   * @param bool $success
   *   Indicates whether the batch process finish successfully.
   * @param array $results
   *   Contains the output from the batch operations.
   * @param array $operations
   *   A list of operations that were processed.
   */
  public static function batchFinished($success, $results, $operations) {
    if ($success && isset($results['count'])) {
      $count = $results['count'];
      $count_for_theme = NULL;
      if (isset($results['count']['matches'])) {
        // Handle regex results.
        $count_for_theme = $results['count']['matches'];
      }
      elseif (isset($results['count']['entities'])) {
        // Handle other results.
        $count_for_theme = $results['count']['entities'];
      }
      else {
        // Handle other results.
        $count_for_theme = $results['count'];
      }
      // $count expected to be a numerical value.
      unset($results['count']);
      $renderable = [
        // @todo '#theme' property with proper valid twig
        //'#theme' => 'scanner_results',
        '#data' => ['values' => $results, 'count' => $count_for_theme],
      ];
      $scannerStore = \Drupal::service('tempstore.private')->get('scanner');
      // Persist the results to the tempstore.
      $scannerStore->set('results', $renderable);
    }
    else {
      \Drupal::messenger()->addMessage(t('There were some errors.'));
    }
    if (!isset($count['matches'])) {
      $count['matches'] = 0;
      $count['entities'] = 0;
    }
    \Drupal::messenger()->addMessage(t('Found @totalmatches total matches in @entities fields. [Entities: @diffentities | Languages: @difflanguages | Fields: @difffields]', [
      '@totalmatches' => $count['summary']['total'],
      '@entities' => $count['matches'],
      '@diffentities' => count($count['summary']['entities']),
      '@difflanguages' => count($count['summary']['languages']),
      '@difffields'=> count($count['summary']['fields']),
      ]));
  }
}
