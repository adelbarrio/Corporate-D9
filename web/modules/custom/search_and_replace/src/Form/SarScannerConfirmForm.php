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
    ];
    foreach ($operations as $value) {
      $values[$value] = $scannerStore->get($value);
    }
    return $build;
  }

}
