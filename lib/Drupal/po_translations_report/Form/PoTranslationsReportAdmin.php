<?php

/**
 * @file
 * Contains \Drupal\po_translations_report\Form\PoTranslationsReportAdmin.
 */

namespace Drupal\po_translations_report\Form;

use Drupal\Core\Form\ConfigFormBase;

class PoTranslationsReportAdmin extends ConfigFormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormID()
  {
    return 'potranslationsreportadmin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state)
  {
    $config = $this->config('po_translations_report.admin_config');
    $form['folder_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Folder path'),
      '#description' => $this->t('Add a path relative to Drupal Root.'),
      '#default_value' => $config->get('folder_path')
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state)
  {
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state)
  {
    parent::submitForm($form, $form_state);

    $this->config('po_translations_report.admin_config')
          ->set('folder_path', $form_state['values']['folder_path'])
        ->save();
  }
}
