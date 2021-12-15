<?php

namespace Drupal\osha_lingua_tools\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Define form to copy publication translations
 */

class OshaLinguaToolsForm extends FormBase {

	/**
	 * {@inheritdoc}
	 */
	public function getFormId(){
		return 'osha_lingua_tools_form';
	}

	/*
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state){
$country_options = osha_lingua_tools_get_country_codes();
  $form['node'] = array(
    '#type' => 'textfield',
    '#required' => TRUE,
    '#title' => t('Choose a publication by title'),
    '#autocomplete_path' => 'publication/node/autocomplete',
  );
  $form['prefix_title'] = array(
    '#title' => 'Prefix title',
    '#type' => 'textfield',
    '#states' => array(
      'disabled' => array(
        '#edit-copy-option input[type="radio"]' => array('value' => 'one'),
      ),
    ),
  );
  $form['suffix_title'] = array(
    '#title' => 'Suffix title',
    '#type' => 'textfield',
    '#states' => array(
      'disabled' => array(
        '#edit-copy-option input[type="radio"]' => array('value' => 'one'),
      ),
    ),
  );
  $form['copy_option'] = array(
    '#type' => 'radios',
    '#title' => '',
    '#options' => array(
      'publication' => 'Publication',
      'one' => 'One publication for each country',
    ),
    '#default_value' => 'publication',
  );
  $form['publication_per_country'] = array(
    '#type' => 'fieldset',
  );
  $form['publication_per_country']['select_all_countries'] = array(
    '#type' => 'checkbox',
    '#title' => t('Select all countries'),
    '#states' => array(
      'disabled' => array(
        '#edit-copy-option input[type="radio"]' => array('value' => 'publication'),
      ),
    ),
  );
    $form['publication_per_country']['countries'] = array(
      '#type' => 'select',
      '#title' => '',
      '#options' => $country_options,
      '#multiple' => TRUE,
      '#chosen' => FALSE,
      '#size' => 20,
      '#states' => array(
        'disabled' => array(
          '#edit-copy-option input[type="radio"]' => array('value' => 'publication'),
        ),
      ),
    );

    $form['create'] = array(
      '#type' => 'submit',
      '#value' => t('Copy and translate'),
    );
    $form['preview'] = array(
      '#type' => 'submit',
      '#submit' => array('osha_copy_publication_translation_preview'),
      '#value' => t('Preview translations'),
    );
    $form['translation_info'] = [
	    '#type' => 'markup',
	    '#markup' => $form_state->getValue('translation_info')
    ];
    return $form;
  }

	/*
	 * {@inheritdoc}
	 */
	public function submitForm (array &$form, FormStateInterface $form_state){
		//$form_state->setErrorByName('lalala','hasta aqui');
    $values = $form_state->getValues();
    $prefix_title = $values['prefix_title'];
    $suffix_title = $values['suffix_title'];
    $copy_option = $values['copy_option'];
    $countries = $values['countries'];
    if (!$prefix_title && !$suffix_title) {
      if (!$copy_option) {
        \Drupal::messenger()->addError(t('No prefix or suffix!'));
        return;
      }
    }
    if ($copy_option == 'one') {
      if (!$countries) {
        \Drupal::messenger()->addError(t('No countries selected!'));
        return;
      }
      return osha_lingua_tools_copy_one_publication_for_each_country($form, $form_state);
    }
    $nid = $values['node'];
    $nid = explode("[", explode("]", $nid)[0])[1];
    dpm($nid);
    osha_copy_publication_translation_original($nid, $form_state, $prefix_title, $suffix_title);
	}
}
