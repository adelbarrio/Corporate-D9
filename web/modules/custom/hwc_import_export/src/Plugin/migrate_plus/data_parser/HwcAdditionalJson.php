<?php

namespace Drupal\hwc_import_export\Plugin\migrate_plus\data_parser;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Json as MigrateJson;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

/**
 * Obtain JSON data for migration.
 *
 * @DataParser(
 *   id = "hwc_additional_json",
 *   title = @Translation("NCW IMPORT JSON")
 * )
 */
class HwcAdditionalJson extends MigrateJson implements ContainerFactoryPluginInterface {

  /**
   * The Config Factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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

    $instance->configFactory = $container->get('config.factory');
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceData($url) {
    // NCW URL configuration.
    $config = $this->configFactory->getEditable('hwc_import_export.config');
    $nm_root_url = $config->get($url . '_endpoint');

    // Get the default items.
    $items =  parent::getSourceData($nm_root_url . $config->get($url . '_' . $this->configuration['content_type']));

    $items_new = [];
    foreach ($items as $i => $item) {
      $nm_item = $item['item'];
      if (!array_key_exists($nm_item['nid_1'], $items_new)) {
        $items_new[$nm_item['nid_1']]['item'] = [
          'nid' => $nm_item['nid_1'],
          'resources' => [],
        ];
      }

      $items_new[$nm_item['nid_1']]['item']['resources'][] = [
        'target_id' => $nm_item['nid'],
      ];
    }

    return $items_new;
  }

}
