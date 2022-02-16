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
 *   id = "hwc_json",
 *   title = @Translation("NCW IMPORT JSON")
 * )
 */
class HwcJson extends MigrateJson implements ContainerFactoryPluginInterface {

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

    $ct = $this->configuration['content_type'];

    // Get the default items.
    $items =  parent::getSourceData($nm_root_url . $config->get($url . '_' . $this->configuration['content_type']));
    //return $items;
    $items_new = [];
    foreach ($items as $i => $item) {
      $nm_item = $item['item'];
      $nm_nid = $nm_item['nid'];
      $nm_url = $nm_root_url . '/export/node/' . $nm_nid;
      try {
        $response = $this->getDataFetcherPlugin()->getResponseContent($nm_url);
        $response_data = json_decode($response, TRUE);
        $lang = $this->configuration['constants']['lang'];
        if (!empty($response_data) && array_key_exists($lang, $response_data['translations']['data'])) {
          // Preprocess "field_publication_type".
          if (array_key_exists('field_publication_type', $response_data)) {
            $response_data['field_publication_type'] = [$response_data['field_publication_type']];
          }
          $items_new[$i]['item'] = $response_data;
        }
      } catch (\Exception $e) {

      }

      if ($i == 5) {
        //return $items_new;
      }
    }
    return $items_new;
  }

}
