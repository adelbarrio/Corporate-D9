<?php

namespace Drupal\hwc_import_export\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * General class for Nm settings form.
 */
class HwcConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hwc_import_export.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hwc_import_export_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config('hwc_import_export.config');

    $form['ncw_events'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Events endpoint URI'),
      '#description' => $this->t('Uri that will be appended to the Root endpoint URL defined'),
      '#default_value' => $config->get('ncw_events'),
      '#required' => TRUE,
    ];

    $form['ncw_add_publication'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publications add res endpoint URI'),
      '#description' => $this->t('Uri that will be appended to the Root endpoint URL defined'),
      '#default_value' => $config->get('ncw_add_publication'),
      '#required' => TRUE,
    ];

    $form['ncw_publication'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publications endpoint URI'),
      '#description' => $this->t('Uri that will be appended to the Root endpoint URL defined'),
      '#default_value' => $config->get('ncw_publication'),
      '#required' => TRUE,
    ];

    $form['ncw_add_news'] = [
      '#type' => 'textfield',
      '#title' => $this->t('News add res endpoint URI'),
      '#description' => $this->t('Uri that will be appended to the Root endpoint URL defined'),
      '#default_value' => $config->get('ncw_add_news'),
      '#required' => TRUE,
    ];

    $form['ncw_news'] = [
      '#type' => 'textfield',
      '#title' => $this->t('News endpoint URI'),
      '#description' => $this->t('Uri that will be appended to the Root endpoint URL defined'),
      '#default_value' => $config->get('ncw_news'),
      '#required' => TRUE,
    ];

    $form['ncw_add_highlights'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Highlights add res endpoint URI'),
      '#description' => $this->t('Uri that will be appended to the Root endpoint URL defined'),
      '#default_value' => $config->get('ncw_add_highlights'),
      '#required' => TRUE,
    ];

    $form['ncw_pa_highlights'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PA Highlights endpoint URI'),
      '#description' => $this->t('Uri that will be appended to the Root endpoint URL defined'),
      '#default_value' => $config->get('ncw_pa_highlights'),
      '#required' => TRUE,
    ];

    $form['ncw_add_infographic'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Infographics add res endpoint URI'),
      '#description' => $this->t('Uri that will be appended to the Root endpoint URL defined'),
      '#default_value' => $config->get('ncw_add_infographic'),
      '#required' => TRUE,
    ];

    $form['ncw_infographic'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Infographic endpoint URI'),
      '#description' => $this->t('Uri that will be appended to the Root endpoint URL defined'),
      '#default_value' => $config->get('ncw_infographic'),
      '#required' => TRUE,
    ];

    $form['ncw_press_release'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Press releases endpoint URI'),
      '#description' => $this->t('Uri that will be appended to the Root endpoint URL defined'),
      '#default_value' => $config->get('ncw_press_release'),
      '#required' => TRUE,
    ];

    $form['ncw_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('NCW endpoint URLL'),
      '#default_value' => $config->get('ncw_endpoint'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the filled values.
    $values = $form_state->getValues();

    // Store the values in the config form.
    $this->config('hwc_import_export.config')
      ->set('ncw_events', $values['ncw_events'])
      ->set('ncw_add_publication', $values['ncw_add_publication'])
      ->set('ncw_publication', $values['ncw_publication'])
      ->set('ncw_add_news', $values['ncw_add_news'])
      ->set('ncw_news', $values['ncw_news'])
      ->set('ncw_add_highlights', $values['ncw_add_highlights'])
      ->set('ncw_pa_highlights', $values['ncw_pa_highlights'])
      ->set('ncw_add_infographic', $values['ncw_add_infographic'])
      ->set('ncw_infographic', $values['ncw_infographic'])
      ->set('ncw_press_release', $values['ncw_press_release'])
      ->set('ncw_endpoint', $values['ncw_endpoint'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
