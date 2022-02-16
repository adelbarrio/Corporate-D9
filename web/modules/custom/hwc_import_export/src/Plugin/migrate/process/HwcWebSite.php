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
 * @MigrateProcessPlugin(
 *   id = "hwc_website",
 * )
 */
class HwcWebSite extends ProcessPluginBase implements ContainerFactoryPluginInterface {

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
      return [];
    }

    $res = [];
    $values = explode(',', $value);
    foreach ($values as $delta => $value) {
      if ($delta % 2 == 0) {
        $res = [
          'url' => $values[$delta],
          'title' => $values[$delta + 1],
          'attributes' => []
        ];
      }
    }
    return $res;
  }

}
