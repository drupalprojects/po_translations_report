<?php

namespace Drupal\po_translations_report\Plugin\PoTranslationsReportDisplayer;

use Drupal\Core\Form\FormStateInterface;
use Drupal\po_translations_report\DisplayerPluginBase;

/**
 * @PoTranslationsReportDisplayer(
 *   id = "sortable_linked_data_table",
 *   label = @Translation("Sortable Linked Data Table"),
 *   description = @Translation("Displays a sortable html table with figures linked to details pages."),
 * )
 */
class SortableLinkedDataTable extends DisplayerPluginBase {

  /**
   * Renders results in form of HTML table.
   *
   * @param array $results
   *   Array of details per po file.
   *
   * @return string
   *   HTML table represented results.
   */
  public function display($results) {
    
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['sortable_linked_data_table'] = array(
      '#type' => 'markup',
      '#markup' => $this->t('No configuration needed for this display method.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    );
    return $form;
  }

}
