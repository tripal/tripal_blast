<?php
/**
 * @file 
 * Construct configuration form of this module.
 */

namespace Drupal\blast_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines TripalBlastUIBlastSubmitForm class.
 * All-in-one BLAST submission form.
 */
class TripalBlastUIBlastSubmitForm extends ConfigFormBase {
  const SETTINGS = 'blast_ui.settings';
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'blast_ui_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   * Build form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
       
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   * Save configuration.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    return parent::submitForm($form, $form_state);
  }
}