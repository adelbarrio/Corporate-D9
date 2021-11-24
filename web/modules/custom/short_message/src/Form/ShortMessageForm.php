<?php

namespace Drupal\short_message\Form;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Short message form for node entity.
 */
class ShortMessageForm extends ContentEntityForm {

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $rendererService;

  /**
   * Module Extension List.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  private $moduleList;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  private $languageManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, Renderer $rendererService, ModuleExtensionList $moduleList, LanguageManager $languageManager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->rendererService = $rendererService;
    $this->moduleList = $moduleList;
    $this->languageManager = $languageManager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('renderer'),
      $container->get('extension.list.module'),
      $container->get('language_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $modulePath = $this->moduleList->getPath('short_message');
    $nodeDisplayFormatter = 'default';

    $translationLinks = [];
    $nativeLanguages = $this->languageManager->getNativeLanguages();
    foreach ($this->entity->getTranslationLanguages() as $language) {
      if ($this->entity->hasTranslation($language->getId())) {
        $lang = $nativeLanguages[$language->getId()] ?? $language;
        $translation = $this->entity->getTranslation($language->getId());
        $translationLink = $translation->toLink($lang->getName() . '| ')
          ->toRenderable();
        $translationLink['#attributes']['style'] = "text-decoration: none; color: #003399;";
        $translationLinks[] = $translationLink;
      }
    }

    $headerElements = [
      '#theme' => 'short_messages_header',
      '#node_id' => $this->entity->id(),
      '#header_link' => [
        '#type' => 'link',
        '#url' => Url::fromRoute('<front>')->setAbsolute(),
        '#title' => [
          '#theme' => 'image',
          '#uri' => $modulePath . '/images/Osha-EU-logos.png',
        ],
      ],
      '#header_translation_links' => $translationLinks,
    ];
    $header = $this->rendererService->renderPlain($headerElements);

    $fieldElements = [];
    foreach (EntityViewDisplay::collectRenderDisplay($this->entity, $nodeDisplayFormatter)
               ->getComponents() as $fieldName => $fieldInfo) {
      if ($fieldName == 'field_publication_date') {
        $fieldElements[$fieldName] = _short_message_alter_published_date($this->entity);
      }
      elseif (!in_array($fieldName, [
          'links',
          'contextual_links',
          '#contextual_links',
        ]) && $this->entity->hasField($fieldName)) {
        $fieldElements[$fieldName] = $this->entity->get($fieldName)
          ->view($nodeDisplayFormatter);
      }
    }


    $bodyElements = [
      '#theme' => 'short_messages_body',
      '#body_content' => $fieldElements,
      '#node_id' => $this->entity->id(),
      '#bundle' => $this->entity->bundle(),
    ];

    if ($this->entity->get('type')->getString() == 'press_release') {
      /** @var \Drupal\views\ViewExecutable $view */
      $view = Views::getView('press_contacts');
      $bodyElements['#contacts'] = $view->buildRenderable('block_1');
    }

    $body = $this->rendererService->renderPlain($bodyElements);

    $footerElements = [
      '#theme' => 'short_messages_footer',
    ];
    $footer = $this->rendererService->renderPlain($footerElements);

    // Content textarea.
    $form['short_messages_content'] = [
      '#type' => 'text_format',
      '#rows' => '20',
      '#format' => 'full_html',
      '#title' => $this->t('Content'),
      '#default_value' => $header . $body . $footer,
    ];

    // The container for preview content.
    $form['short_messages_markup'] = [
      '#type' => 'markup',
      '#markup' => Markup::create('<div id="preview_message" style="display: none"><iframe id="preview_content" frameborder="0" height="100%" width="100%"></iframe></div>'),
    ];

    $form['#attached']['library'][] = 'short_message/short_message.popup';
    $form['#attached']['library'][] = 'short_message/short_message.copytoclipboard';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    return [
      'short_messages_popup' => [
        '#type' => 'button',
        '#value' => t('Display message'),
        '#attributes' => [
          'onclick' => 'displayDialog(); return false;',
        ],
      ],
      'short_messages_clipboard' => [
        '#type' => 'button',
        '#value' => t('Copy Html to Clipboard'),
        '#attributes' => [
          'class' => [
            'btn',
          ],
          'data-clipboard-target' => '#edit-short-messages-content-value',
          'onclick' => 'return false;',
        ],
      ],
    ];
  }

}
