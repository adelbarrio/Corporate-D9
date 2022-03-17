<?php

namespace Drupal\hwc_crm_partners_migration\Plugin\migrate\process;


use Drupal\media\Entity\Media;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\Core\File\FileSystemInterface;


/**
 * This plugin find the term by name and vocabulary.
 *
 * @code
 * process:
 *   destination_field:
 *     plugin: hwc_image_from_blob
 *     source: source_field
 *     file_name: name
 *     file_type: type
 * @endcode
 * @MigrateProcessPlugin(
 *   id = "hwc_image_from_blob",
 * )
 */
class HwcImageFromBlob extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (empty($value)) {
      throw new MigrateSkipProcessException();
    }

    $originalTitle = $row->getSourceProperty('title');
    $title = preg_replace('![^0-9A-Za-z_.-]!', '', $row->getSourceProperty('title'));

    if ($destination_property == 'field_logo/target_id') {
      $logo = $row->getSourceProperty('field_logo');
      $name = $title . '_logo.' . $row->getSourceProperty('field_logo_1');
    }
    else {
      $logo = $row->getSourceProperty('field_ceo_photo');
      $name = $title . '_ceo.' . $row->getSourceProperty('field_ceo_photo_1');
    }

    $image = base64_decode($logo);
    if ($image == "") {
      if ($destination_property == 'field_logo/target_id') {
        $migrate_executable->message->display("Logo missing or malformed for " . $originalTitle . ".\n", MigrationInterface::MESSAGE_NOTICE);
      }
      else {
        $migrate_executable->message->display("CEO photo missing or malformed for " . $originalTitle . ".\n", MigrationInterface::MESSAGE_NOTICE);
      }
      return NULL;
    }

    $file = file_save_data($image, 'public://partners/' . $name, FileSystemInterface::EXISTS_REPLACE);

    // Delete media if exists.
    $storageHandler = \Drupal::entityTypeManager()->getStorage('media');
    if ($file != FALSE) {
      $previous_media = array_values($storageHandler->loadByProperties(['field_media_image' => $file->id()]));
      if ($previous_media != NULL) {
        $previous_media[0]->delete();
      }
    }

    // Create media.
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