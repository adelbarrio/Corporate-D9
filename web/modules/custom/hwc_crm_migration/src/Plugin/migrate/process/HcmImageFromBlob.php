<?php

namespace Drupal\hwc_crm_migration\Plugin\migrate\process;

use Drupal\media\Entity\Media;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\Core\File\FileSystemInterface;

/**
 * This plugin find the term by name and vocabulary.
 * @code
 * process:
 *   destination_field:
 *     plugin: hcm_ucfirst
 *     source: source_field
 *     file_name: name
 *     file_type: type
 * @endcode
 * @MigrateProcessPlugin(
 *   id = "hcm_image",
 * )
 */
class HcmImageFromBlob extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      throw new MigrateSkipProcessException();
    }

    $name = $row->getSourceProperty('title') . ' ' . $row->getSourceProperty('field_country') . '.' . $row->getSourceProperty('field_logo_type');
    $file = file_save_data(base64_decode($value), 'public://ncw/images/' . $name, FileSystemInterface::EXISTS_REPLACE);
    $media = Media::create(
      [
        'bundle' => 'image',
        'uid' => \Drupal::currentUser()->id(),
        'field_media_image' => [
          'target_id' => $file->id(),
        ],
      ]
    );

    $media->setName($name)->setPublished()->save();

    return $media->id();
  }

}
