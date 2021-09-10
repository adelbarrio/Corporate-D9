<?php

namespace Drupal\ncw_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\MigrateSkipProcessException;

/**
 * This plugin replace body value from new value.
 *
 * @MigrateProcessPlugin(
 *   id = "nm_taxonomy_term",
 * )
 */
class NmTaxonomyTerm extends ProcessPluginBase implements ContainerFactoryPluginInterface{

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      throw new MigrateSkipProcessException();
    }

    $field_wiki_categories = $row->getSourceProperty('field_wiki_categories');

    foreach ($field_wiki_categories as $category) {
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
        'name' => $category['name'],
        'vid' => $category['vocabulary_machine_name'],
      ]);

      /** @var \Drupal\taxonomy\Entity\Term $term */
      $term = reset($term);
      if (!empty($term)) {
        $value[$category['tid']] = [
          'target_id' => $term->id(),
        ];
      }
    }

    return $value;
  }

}
