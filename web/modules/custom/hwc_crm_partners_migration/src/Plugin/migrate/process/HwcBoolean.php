<?php


namespace Drupal\hwc_crm_partners_migration\Plugin\migrate\process;


use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * This plugin find the term by name and vocabulary.
 *
 * @code
 * process:
 *   destination_field:
 *     plugin: hwc_boolean
 *     source: source_field
 * @endcode
 * @MigrateProcessPlugin(
 *   id = "hwc_boolean",
 * )
 */
class HwcBoolean extends ProcessPluginBase{
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($value=="True"){
      return 1;
    }
    return 0;
  }
}
