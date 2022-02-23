<?php

namespace Drupal\hwc_crm_partners_migration\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;


/**
 * This plugin find the term by name and vocabulary.
 *
 * @code
 * process:
 *   destination_field:
 *     plugin: hwc_crm_taxonomy_term_name
 *     source: source_field
 *     vocabulary: vocabulary_name
 *     create: false
 * @endcode
 * @MigrateProcessPlugin(
 *   id = "hwc_crm_taxonomy_term_name",
 * )
 */
class HwcCrmTaxonomyTermName extends HwcCrmTaxonomyTermType {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $orgtype = $row->getSourceProperty('field_orgtype');
    $business_sector = $row->getSourceProperty('field_bussines_sector');
    $partner_type = $row->getSourceProperty('field_partner_type');
    $country = $row->getSourceProperty('field_country');
    $section = $row->getSourceProperty('field_workbench_access');
    return parent::transform($value, $migrate_executable, $row, $destination_property);
  }

}
