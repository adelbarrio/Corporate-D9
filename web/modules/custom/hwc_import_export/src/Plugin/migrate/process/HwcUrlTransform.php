<?php

namespace Drupal\hwc_import_export\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This plugin transform the url of the image to hwc url.
 *
 * @MigrateProcessPlugin(
 *   id = "hwc_url_transform",
 * )
 */
class HwcUrlTransform extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The Config Factory manager.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $nn = str_replace('public://', '', $value);
    $config = $this->configFactory->get('hwc_import_export.config');
    $urls_site = (isset($row->getSource()['urls'])) ? $row->getSource()['urls'][0] : 'default';
    $base = $config->get($urls_site . '_endpoint');
    if (strpos($nn, '.pdf') != 0 || strpos($nn, '.pptx') != 0) {
      return $base . "/sites/default/files/" . $nn;
    }
    return $base . "/sites/default/files/styles/cover_images/public/" . $nn;
  }

}
