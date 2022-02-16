<?php

namespace Drupal\hwc_import_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\taxonomy\Entity\Term;


class HwcTaxonomyExportController extends ControllerBase implements ContainerInjectionInterface {

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

  public function export(Term $term) {
    if (!isset($term) || empty($term)) {
      return new JsonResponse([]);
    }

    // Get each translation.
    $langs = [];
    foreach ($term->getTranslationLanguages() as $lang => $value) {
      $langs[$lang] = $term->getTranslation($lang);
    }

    $data = [
      'tid' => $term->id(),
      'vid' => $term->get('vid')->getString(),
      'name' => $term->getName(),
      'description' => $term->getDescription(),
      'format' => 'full_html',
      'weight' => $term->getWeight(),
      'changed' => $term->getRevisionCreationTime(),
      'uuid' => $term->uuid(),
      'language' => $term->language()->getId(),
      'vocabulary_machine_name' => $term->get('vid')->getString(),
      'description_field' => [
        $term->language()->getId() => [
          'value' => $term->getDescription(),
        ],
      ],
    ];

    foreach ($term->getFieldDefinitions() as $field_name => $definition) {
      if (!$definition instanceof FieldConfig) {
        continue;
      }

      if (!$definition->isTranslatable()) {
        $data += $this->getFieldValue($term, $definition, $field_name);
      }
      else {
        /**
         * @var \Drupal\taxonomy\Entity\Term $term_lang
         */
        foreach ($langs as $lang => $term_lang) {
          $trans_value = $this->getFieldValue($term_lang, $definition, $field_name);
          if (isset($trans_value) && !empty($trans_value)) {
            $data += $trans_value;
          }
        }
      }
    }

    foreach ($langs as $lang => $term_lang) {
      $data['name_field'][$lang] = [
        0 => [
          'value' => $term_lang->getName(),
          'format' => null,
          'safe_value' => $term_lang->getName(),
        ]
      ];

      $data['translations']['original'] = 'en';
      $data['translations']['data'][$lang] = [
        'entity_type' => "taxonomy_term",
        'entity_id' => $term_lang->id(),
        'language' => $lang,
        'source' => "en",
        'status' => $term_lang->isPublished(),
        'translate' => 0,
        'created' => $term_lang->getRevisionCreationTime(),
        'changed' => $term_lang->getChangedTime(),
      ];
    }

    return new JsonResponse($data);
  }

  /**
   * Get the field value.
   *
   * @param \Drupal\taxonomy\Entity\Term $node
   * @param \Drupal\field\Entity\FieldConfig $definition
   * @param $field_name
   *
   * @return array|array[]|\array[][]
   */
  public function getFieldValue(Term $term, FieldConfig $definition, $field_name) {
    $value = $term->get($field_name)->getString();
    if ($definition->getType() == 'list_string') {
      $values = str_replace(' ', '', $term->get($field_name)->getString());
      $values = explode(',', $values);
      $res = [];
      foreach ($values as $value) {
        $res[]['value'] = $value;
      }
      return [
        $field_name => ['und' => $res],
      ];
    }

    if ($definition->getType() == 'string') {
      return [
        $field_name => [
          'und' => [
            '0' => [
              'value' => $value,
              'format' => 'yolo',
              'safe_value' => $value,
            ]
          ],
        ],
      ];
    }
    return (empty($value) ? [] : [$field_name => [$term->language()->getId() => ['value' => $value]]]);
  }

}
