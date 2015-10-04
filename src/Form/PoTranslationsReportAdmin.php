<?php

/**
 * @file
 * Contains \Drupal\po_translations_report\Form\PoTranslationsReportAdmin.
 */

namespace Drupal\po_translations_report\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class PoTranslationsReportAdmin extends ConfigFormBase {
  /**
   * Name of the config being edited.
   */
  const CONFIGNAME = 'po_translations_report.admin_config';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array(static::CONFIGNAME);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'po_translations_report_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIGNAME);
    $form['folder_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Folder path'),
      '#description' => $this->t('Add the complete path to the folder that contains po files.'),
      '#default_value' => $config->get('folder_path'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check if the path is for valid readable folder.
    $folder_path = $form_state->getValue('folder_path');
    if (!is_dir($folder_path)) {
      $form_state->setErrorByName('folder_path', $this->t('%folder_path is not a directory.', array('%folder_path' => $folder_path)));
    }
    else {
      if (!is_readable($folder_path)) {
        $form_state->setErrorByName('folder_path', $this->t('%folder_path is not a readable directory.', array('%folder_path' => $folder_path)));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::CONFIGNAME)
        ->set('folder_path', $form_state->getValue('folder_path'))
        ->save();

    // Show the "configuration is saved" message.
    parent::submitForm($form, $form_state);

    // Redirect to reports page.
    $route = 'po_translations_report.content';
    $form_state->setRedirect($route);
  }

}
