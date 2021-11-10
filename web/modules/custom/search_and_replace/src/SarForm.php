<?php

namespace Drupal\search_and_replace;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SarForm implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity_type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Form alter vesafe content types.
   *
   * @see \hook_form_alter()
   */
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) {
    if ($form_id !== 'scanner_form') {
      return;
    }

    $form['replace']['#weight'] = 99;
    $form['submit_replace']['#weight'] = 100;

    /*$form['options']['bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Bundles'),
      '#options' => $this->getAvailableEntityTypes(),
      '#weight' => -2,
      '#description' => $this->t('If none selected, all bundles will be used in search.'),
    ];

    $form['options']['version'] = [
      '#type' => 'radios',
      '#title' => $this->t('Bundles'),
      '#options' => [0 => $this->t('Published'), 1 => $this->t('Current Draft')],
      '#weight' => -1,
      '#default_value' => 0,
      '#description' => $this->t('Select the versions of the nodes on which you want to perform the search.'),
    ];*/

    unset($form['options']['published']);

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
