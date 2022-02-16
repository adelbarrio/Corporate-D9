<?php

namespace Drupal\hwc_import_export\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\taxonomy\Entity\Term;

/**
 * This plugin find the term by name and vocabulary.
 * @code
 * process:
 *   destination_field:
 *     plugin: hwc_taxonomy_term_type
 *     source: source_field
 *     vocabulary: vocabulary_name
 *     create: false
 * @endcode
 * @MigrateProcessPlugin(
 *   id = "hwc_taxonomy_term_type",
 * )
 */
class HwcTaxonomyTermType extends ProcessPluginBase implements ContainerFactoryPluginInterface {

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
    if (empty($value) || empty($this->configuration['vocabulary'])) {
      throw new MigrateSkipProcessException();
    }

    $term = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'name' => $value,
      'vid' => $this->configuration['vocabulary'],
    ]);

    /** @var \Drupal\taxonomy\Entity\Term $term */
    $term = reset($term);
    if (!empty($term)) {
      $value = [
        'target_id' => $term->id(),
      ];
    }
    else {
      if ($this->configuration['create']) {
        $term = Term::create(
          [
            'parent' => [],
            'name' => $value,
            'vid' => $this->configuration['vocabulary'],
          ]
        );

        if (array_key_exists('data', $this->configuration)) {
          foreach ($this->configuration['data'] as $key => $key_value) {
            $term->set($key, $key_value);
          }
        }

        $term->save();
        $value = [
          'target_id' => $term->id(),
        ];
      }
    }

    return $value;
  }

}
