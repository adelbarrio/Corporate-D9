<?php

namespace Drupal\osha_import_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\field\Entity\FieldConfig;
use Drupal\file_entity\Entity\FileEntity;

/**
 * OieNodeExportController class.
 */
class OieNodeExportController extends ControllerBase implements ContainerInjectionInterface{

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Export the node content.
   *
   * @param \Drupal\node\Entity\Node $node
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function export(Node $node) {
    if (!isset($node) || empty($node)) {
      return new JsonResponse([]);
    }

//Get providers fields for msds content type
    if ($node->bundle()=="musculoskeletal_disorders") {
      $paragraph = $node->field_msd_provider->getValue();

      foreach ($paragraph as $element) {
        $provider = \Drupal\paragraphs\Entity\Paragraph::load($element['target_id']);
        $provname = $provider->field_provider_english_->getValue();
        $provoriginal = $provider->field_provider_original_->getValue();
      }
    }

    // Get each translation.
    $langs = [];
    foreach ($node->getTranslationLanguages() as $lang => $value) {
      $langs[$lang] = $node->getTranslation($lang);
    }


    $data = [
      'vid' => $node->get('vid')->getString(),
      'uid' => $node->get('uid')->getString(),
      'title' => $node->get('title')->getString(),
      'status' => $node->get('status')->getString(),
      'promote' => 0,
      'sticky' => $node->get('sticky')->getString(),
      'language' => $node->language()->getId(),
      'nid' => $node->get('nid')->getString(),
      'type' => $node->get('type')->getString(),
      'created' => $node->get('created')->getString(),
      'changed' => $node->get('changed')->getString(),
      'translate' => (int)$node->isTranslatable(),
      'uuid' => $node->get('uuid')->getString(),
      'revision_timestamp' => $node->get('revision_timestamp')->getString(),
      'revision_uid' => $node->get('revision_uid')->getString(),
      'pathalias' => mb_substr($node->get('path')->getString() ,0,mb_strpos($node->get('path')->getString(),',')),
      'provname' => $provname,
      'provoriginal' => $provoriginal,
    ];

    foreach ($node->getFieldDefinitions() as $field_name => $definition) {
      if (!$definition instanceof FieldConfig) {
        continue;
      }

      if (!$definition->isTranslatable()) {
        $data += $this->getFieldValue($node, $definition, $field_name);
      }
      else {
        /**
         * @var \Drupal\node\Entity\Node $node_lang
         */
        foreach ($langs as $lang => $node_lang) {
          $trans_value = $this->getFieldValue($node_lang, $definition, $field_name);
          if (isset($trans_value) && !empty($trans_value)) {
            $data = array_merge_recursive($data, $trans_value);
          }
        }
      }
    }

    foreach ($langs as $lang => $node_lang) {
      $data['title_field'][$lang] = [
        [
          'value' => $node_lang->getTitle(),
          'format' => "",
          'safe_value' => $node_lang->getTitle(),
        ]
      ];

      $data['translations']['original'] = 'en';
      $data['translations']['data'][$lang] = [
        'entity_type' => "node",
        'entity_id' => $node_lang->id(),
        'language' => $lang,
        'source' => "en",
        'status' => $node_lang->isPublished(),
        'translate' => 0,
        'created' => $node_lang->getRevisionCreationTime(),
        'changed' => $node_lang->getChangedTime(),
      ];
    }

    /** @var \Drupal\user\Entity\User  $owner */
    $owner = $node->getOwner();
    $data['name'] = $owner->getAccountName();
    $data['picture'] = $owner->get('user_picture');

    return new JsonResponse($data);
  }

  /**
   * Get the field value.
   *
   * @param \Drupal\node\Entity\Node $node
   * @param \Drupal\field\Entity\FieldConfig $definition
   * @param $field_name
   *
   * @return array|array[]|\array[][]
   */
  public function getFieldValue(Node $node, FieldConfig $definition, $field_name) {
    $value = $node->get($field_name)->getString();

    // Ref entity process.
    if ($definition->getType() == 'entity_reference') {
      $values = $node->get($field_name)->getValue();
      if (!empty($values)) {
        $settings = $definition->getSettings();
        $referers = [];

        // Media.
        if ($settings['target_type'] == 'media') {
          foreach ($values as $value) {
            $value = $this->getMediaValues($value['target_id'], $settings);
            if (isset($value) && !empty($value)) {
              $referers[$node->language()->getId()] = [$value];
            }
          }

          if ($field_name == 'field_image_media') {
            $field_name = 'field_image';
          }

          if ($field_name == 'field_file_media') {
            $field_name = 'field_file';
          }

          return [$field_name => $referers];
        }

        // Taxonomy Terms.
        if ($settings['target_type'] == 'taxonomy_term') {
          foreach ($values as $value) {
            if ($field_name == 'field_publication_type') {
              $referers += $this->getTaxonomyValues($value['target_id'], $settings);
            }
            else {
              $referers[] = $this->getTaxonomyValues($value['target_id'], $settings);
            }
          }
          return [$field_name => $referers];
        }

        // Node.
        if ($settings['target_type'] == 'node') {
          foreach ($values as $value) {
            $referers[$node->language()->getId()][] = $value;
          }

          return [$field_name => $referers];
        }
      }
      return [$field_name => []];
    }

    // Datetime process.
    if ($definition->getType() == 'datetime') {
      $value = $node->get($field_name)->getString();
      if (!empty($value)) {
        $value = strtotime($value);
        $value = \date('Y-m-d h:i:s', $value);
        return [
          $field_name => [
            'und' => [
              'value' => $value,
              "timezone" => "Europe/Madrid",
              "timezone_db" => "Europe/Madrid",
              "date_type" => "datetime",
            ],
          ]
        ];
      }
    }

    // Comment process.
    if ($definition->getType() == 'comment') {
      $values = $node->get($field_name)->getValue();
      return [
        'cid' => ($values['cid'] ?? []),
        'last_comment_timestamp' => ($values[0]['last_comment_timestamp'] ?? []),
        'last_comment_name' => ($values[0]['last_comment_name'] ?? []),
        'last_comment_uid' => ($values[0]['last_comment_uid'] ?? []),
        'comment_count' => ($values[0]['comment_count'] ?? []),
      ];
    }

    // Interger or decimal.
    if ($definition->getType() == 'integer' || $definition->getType() == 'decimal' || $definition->getType() == 'string') {
      return [
        $field_name => [
          $node->language()->getId() => [['value' => $value]],
        ],
      ];
    }

    // Text long.
    if ($definition->getType() == 'text_long') {
      $values = $node->get($field_name)->getValue()[0];
      if ($field_name == 'field_summary_html') {
        $field_name = 'field_summary';
      }

      return [
        $field_name => [
          $node->language()->getId() => [
            '0' => [
              'value' => $values['value'],
              'format' => $values['format'],
            ]
          ],
        ],
      ];
    }

    // Image.
    if ($definition->getType() == 'image') {
      $values = $node->get($field_name)->getValue();
      if (!empty($values)) {
        $settings = $definition->getSettings();
        $referers = [];
        if ($settings['target_type'] == 'file') {
          foreach ($values as $value) {
            $value = $this->getFileValues($value['target_id']);
            if (isset($value) && !empty($value)) {
              $referers[$field_name][] = $value;
            }
          }
        }
        return $referers;
      }
    }

    if ($definition->getType() == 'text_with_summary') {
      $values = $node->get($field_name)->getValue()[0];
      return [$field_name => [$node->language()->getId() => [['value' => $values['value'], 'format' => $values['format']]]]];
    }

    if ($definition->getType() == 'list_string' || $definition->getType() == 'language_field') {
      $values = $node->get($field_name)->getValue();
      $referers = [];
      foreach ($values as $value) {
        $referers[] = $value;
      }
      return [$field_name => [$node->language()->getId() => $referers]];
    }


    return (empty($value) ? [] : [$field_name => [$node->language()->getId() => [['value' => $value]]]]);
  }

  /**
   * Get the taxonomy term values.
   *
   * @param $id
   * @param array $settings
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getTaxonomyValues($id, array $settings) {
    /** @var \Drupal\taxonomy\Entity\Term $term */
    $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($id);
    if (!isset($term)) {
      return [];
    }
    $term_data = [
      'tid' => $term->id(),
      'vid' => $term->get('vid')->getString(),
      'name' => $term->getName(),
      'description' => $term->getDescription(),
      'format' => $term->getFormat(),
      'weight' => $term->getWeight(),
      'changed' => $term->getRevisionCreationTime(),
      'uuid' => $term->uuid(),
      'language' => $term->language()->getId(),
      'vocabulary_machine_name' => $term->get('vid')->getString(),
      'description_field' => [
        'en' => [
          [
            'value' => $term->getDescription(),
            'format' => $term->getFormat(),
          ]
        ],
      ],
    ];

    foreach ($term->getFieldDefinitions() as $field_name => $definition) {
      if (!$definition instanceof FieldConfig) {
        continue;
      }

      if (!$definition->isTranslatable()) {
        $term_value = $term->get($field_name)->getString();

        if ($field_name == 'field_excluded_from') {
          $values = str_replace(' ', '', explode(',', $term_value));
          foreach ($values as $value) {
            $term_data[$field_name][$term->language()->getId()][]['value'] = $value;
          }
          continue;
        }

        if ($field_name == 'field_tags_code' || $field_name == 'field_publication_type_code') {
          $term_data[$field_name]['und'] = [
            'value' => $term_value,
            'safe_value' => $term_value,
          ];
          continue;
        }

        // Image.
        if ($definition->getType() == 'image') {
          $values = $term->get($field_name)->getValue();
          if (!empty($values)) {
            $settings = $definition->getSettings();
            $referers = [];
            if ($settings['target_type'] == 'file') {
              foreach ($values as $value) {
                $value = $this->getFileValues($value['target_id']);
                if (isset($value) && !empty($value)) {
                  $term_data[$field_name][][$term->language()
                    ->getId()][] = $value;
                }
              }
            }
          }
          continue;
        }

        $term_data[$field_name][][$term->language()->getId()]['value'] = $term_value;
      }
    }

    $term_data['translations'] = [
      'original' => "en",
      'data' => [],
    ];

    if ($term->isTranslatable()) {
      foreach ($term->getTranslationLanguages() as $lang => $value) {
        $term_lang = $term->getTranslation($lang);
        $term_data['name_field'][$lang] = [
          'value' => $term_lang->getName(),
          'format' => "",
          'safe_value' => $term_lang->getName(),
        ];

        $term_data['translations']['data'][$lang] = [
          'entity_type' => "taxonomy_term",
          'entity_id' => $term_lang->id(),
          'language' => $lang,
          'source' => "en",
          'status' => $term->isPublished(),
          'translate' => 0,
          'created' => $term_lang->getRevisionCreationTime(),
          'changed' => $term_lang->getChangedTime(),
        ];
      }
    }

    $term_data['name_original'] = $term->getName();

    return $term_data;
  }

  /**
   * Get the media values.
   *
   * @param $id
   * @param array $settings
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getMediaValues($id, array $settings) {
    /** @var \Drupal\media\Entity\Media $ref */
    $media = $this->entityTypeManager->getStorage('media')->load($id);
    $res = [];
    $field_ref = NULL;

    if ($media->bundle() == 'image' ||
      $media->bundle() == 'image_caption' ||
      $media->bundle() == 'image_and_link'
    ) {
      if ($media->bundle() == 'image_and_link') {
        $field_ref = 'field_media_image_1';
      }
      else {
        $field_ref = 'field_media_image';
      }

    }
    elseif ($media->bundle() == 'document') {
      $field_ref = 'field_media_document';
    }

    $media_file_values = $media->get($field_ref)->getValue()[0];
    $res = $this->getFileValues($media_file_values['target_id']);

    if (strpos($media->bundle(), 'image') > -1) {
      $res['type'] = $media->bundle();
      $res['field_file_image_alt_text'] = $media_file_values['alt'];
      $res['field_file_image_title_text'] = $media_file_values['title'];
      $res['field_file_description'] = $media->get('field_description')->getValue();
    }

    return $res;
  }

  public function getFileValues($fid) {
    /** @var \Drupal\file_entity\Entity\FileEntity $file */
    $file = $this->entityTypeManager->getStorage('file')->load($fid);
    return $this->getNormalicedMediaValues($file);
  }

  /**
   * Get the normaliced data.
   *
   * @param \Drupal\file_entity\Entity\FileEntity $file
   *
   * @return array
   */
  public function getNormalicedMediaValues(FileEntity $file) {
    return [
      'fid' => $file->id(),
      'filename' => $file->getFilename(),
      'uri' => $file->getFileUri(),
      'filesize' => $file->getSize(),
      'filemime' => $file->getMimeType(),
      'status' => $file->get('status')->getString(),
      'timestamp' => $file->getCreatedTime(),
      'uuid' => $file->uuid(),
    ];
  }

}
