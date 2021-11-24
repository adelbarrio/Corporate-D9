<?php

namespace Drupal\search_and_replace;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Alter Form for performing search.
 */
class SarForm implements ContainerInjectionInterface {

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
