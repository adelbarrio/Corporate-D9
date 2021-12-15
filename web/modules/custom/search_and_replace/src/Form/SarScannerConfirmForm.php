<?php

namespace Drupal\search_and_replace\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\scanner\Form\ScannerConfirmForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirmation Form operations messages.
 */
class SarScannerConfirmForm extends ScannerConfirmForm {

  /**
   * The Private temporary storage factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  private $tempStore;

  /**
   * {@inheritdoc}
   */
  public function __construct(PrivateTempStoreFactory $tempStore) {
    parent::__construct($tempStore);
    $this->tempStore = $tempStore;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $build = parent::buildForm($form, $form_state);

    $scannerStore = $this->tempStore->get('scanner');
    $operations = [
      'search',
      'replace',
      'mode',
      'wholeword',
      'regex',
      'preceded',
      'followed',
      'published',
      'language',
      'results',
      'results_final',
    ];
    foreach ($operations as $value) {
      $values[$value] = $scannerStore->get($value);
    }
    // reduce operations to selected entity_type:bundle:field
    $operations = array_filter($values['results_final']);
    $build['msg']['#markup'] .= "<br>Number of selected operations: " . sizeof($operations);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $scannerStore = $this->tempStore->get('scanner');
    foreach ([
      'search',
      'replace',
      'mode',
      'wholeword',
      'regex',
      'preceded',
      'followed',
      'published',
      'language',
      'results',
      'results_final',
    ] as $value) {
      $values[$value] = $scannerStore->get($value);
    }
    $fields = \Drupal::config('scanner.admin_settings')->get('fields_of_selected_content_type');
    $operations = [];
    // Build an array of batch operation jobs.
    // Remove non-selected elements
    $selected_items = array_filter($values['results_final']);
    // reduce array by entity_type:bundle:field
    $selected_fields = [];
    foreach ($selected_items as $selected_item){
	    list($entity_type,$bundle,$field,,) = explode(":",$selected_item);
	    $selected_fields["$entity_type:$bundle:$field"] = 1;
    }
    // operations only on selected fields
    foreach ( $selected_fields as $selected_field=>$v){
      list($entity_type,$bundle,$field,,) = explode(":",$selected_field);
      $selected_values = $values;
      unset($selected_values['results']['#data']);
      $selected_values['results']['#data']['values'][$entity_type][$bundle][$field] = $values['results']['#data']['values'][$entity_type][$bundle][$field];
      $selected_values['results']['#data']['count'] = sizeof($selected_values['results']['#data']['values'][$entity_type][$bundle][$field]);
      $operations[] = [
        '\Drupal\scanner\Form\ScannerConfirmForm::batchReplace',
        [
          "$entity_type:$bundle:$field",
          $selected_values,
        ],
      ];
    }

    $batch = [
      'title' => $this->t('Scanner Replace Batch'),
      'operations' => $operations,
      'finished' => '\Drupal\search_and_replace\Form\SarScannerConfirmForm::batchFinished',
      'progress_message' => $this->t('Processed @current out of @total'),
    ];
    batch_set($batch);
    // Redirect to the scanner page after the batch is done.
    $form_state->setRedirect('scanner.admin_content');
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
    $count = 0;
    $messenger = \Drupal::messenger();
    if ($success) {
      if (!empty($results['data'])) {
        foreach ($results['data'] as $value) {
          if (count($value) == 2) {
            $count++;
          }
          else {
            // Something went wrong.
            \Drupal::logger('scanner')->error('An issue has occured during the replace operation.');
          }
        }
        $results['count'] = $count;
        $messenger->addMessage(t('@count field processed.', [
          '@count' => $count,
        ]));
        $connection = \Drupal::service('database');
        // Insert to row into the scanner table so that the
        // action can be undone in the future.
        $connection->insert('scanner')
          ->fields([
            'undo_data' => serialize($results['data']),
            'undone' => 0,
            'searched' => $results['inputs']['search'],
            'replaced' => $results['inputs']['replace'],
            'count' => $count,
            'time' => \Drupal::time()->getRequestTime(),
          ])
          ->execute();
      }
    }
    else {
      $message = t('There were some errors.');
      $messenger->addMessage($message);
    }
  }
}
