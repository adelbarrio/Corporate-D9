<?php

namespace Drupal\osha_import_export\Plugin\migrate_plus\data_parser;

use Drupal\Core\Database\Connection;
use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Json as MigrateJson;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Obtain JSON data for migration.
 *
 * @DataParser(
 *   id = "oie_json",
 *   title = @Translation("NCW IMPORT JSON")
 * )
 */
class OieJson extends MigrateJson implements ContainerFactoryPluginInterface {

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
    $config = $this->configFactory->getEditable('osha_import_export.config');
    $nm_root_url = $config->get($url . '_endpoint');

    // Get the default items.
    $items =  parent::getSourceData($nm_root_url . $config->get($url . '_' . $this->configuration['content_type']));
    $items_new = [];
    foreach ($items as $i => $item) {
      $nm_item = $item['item'];
      //$nm_nid = $nm_item['nid'];
      $nm_nid = '';
      $nm_url = $nm_root_url . '/export/node/' . $nm_item['nid'];
      $response = $this->getDataFetcherPlugin()->getResponseContent($nm_url);
      $response_data = json_decode($response, TRUE);

      if (empty($response_data) || !array_key_exists($this->configuration['constants']['lang'], $response_data['translations']['data'])) {
        continue;
      }

      // Get the data decoded.
      $decoded_data = json_decode($response, TRUE);

      // If the node already exists, update it.
      $node = $this->entityTypeManager->getStorage('node')->loadByProperties(['title' => $decoded_data['title'],'type' => $this->configuration['content_type']]);
      if (!empty($node)) {
        /** @var \Drupal\node\Entity\Node $node */
        $node = reset($node);
        $decoded_data['nid'] = $node->id();
      }else{
        //Get next not use the old email avoiding data lost
        $results = \Drupal::database()->query('select max(nid) nextid from node')->fetchAll();
        $decoded_data['nid'] = $results[0]->nextid + 1;
      }
      //The content from Hwc whit the show in ncw unchecked will be migrated in draft status
      if ($decoded_data['field_show_in_ncw']["und"][0]["value"]==0 && $url=='hwc'){
        $decoded_data["status"] = 0;
        $decoded_data["workbench_moderation"]["current"]["state"] = 'draft';
        unset($decoded_data["workbench_moderation"]["published"]);
      }

      // Set the new item.
      $items_new[$i]['item'] = $decoded_data;
    }

    return $items_new;
  }

}
