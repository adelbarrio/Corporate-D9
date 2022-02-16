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
 *   id = "hwc_body",
 * )
 */
class HwcBody extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($value) {
      $value = preg_replace('#(href|src)="([^:"]*)("|(?:(?:%20|\s|\+)[^"]*"))#','$1="https://osha.europa.eu/$2$3', $value);
    }

    return $value;
  }

}
