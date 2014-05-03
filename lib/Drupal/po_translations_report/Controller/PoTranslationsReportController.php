<?php

namespace Drupal\po_translations_report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Gettext\PoStreamReader;

class PoTranslationsReportController extends ControllerBase {

  private $translated_count = 0;
  private $untranslated_count = 0;
  private $not_allowed_translation_count = 0;
  private $total_count = 0;
  private $report_results = array();

  /**
   * content
   * @return string
   */
  public function content() {
    $config = \Drupal::config('po_translations_report.admin_config');
    $folder_path = $config->get('folder_path');
    $folder = new \DirectoryIterator($folder_path);
    $po_found = FALSE;
    foreach ($folder as $fileinfo) {
      if ($fileinfo->isFile() && $fileinfo->getExtension() == 'po') {
        // Flag we found at least one po file in this directory.
        $po_found = TRUE;
        // Instantiate and initialize the stream reader for this file.
        $reader = new PoStreamReader();
        $reader->setURI($fileinfo->getRealPath());

        try {
          $reader->open();
        } catch (\Exception $exception) {
          throw $exception;
        }

        $header = $reader->getHeader();
        if (!$header) {
          throw new \Exception('Missing or malformed header.');
        }
        while ($item = $reader->readItem()) {
          if (!$item->isPlural()) {
            $this->translationReport($item->getTranslation());
          }
          else {
            // Plural case.
            $plural = $item->getTranslation();
            foreach ($item->getSource() as $key => $source) {
              $this->translationReport($plural[$key]);
            }
          }
        }

        $this->setReportResultsSubarray(array(
          'file_name' => $fileinfo->getFilename(),
          'translated' => $this->getTranslatedCount(),
          'untranslated' => $this->getUntranslatedCount(),
          'not_allowed_translations' => $this->getNotAllowedTranslatedCount(),
          'total_per_file' => $this->getTotalCount(),
            )
        );
      }
    }
    // Handle the case where no po file could be found in the provided path.
    if (!$po_found) {
      $message = t('No po was found in %folder', array('%folder' => $folder_path));
      drupal_set_message($message, 'warning');
    }

    // Now that all result data is filled, add a row with the totals.
    // Add totals row at the end.
    $this->addTotalsRow();


    return $this->display();
  }

  /**
   * Displays the results in a sortable table.
   * @see core/includes/sorttable.inc
   */
  public function display() {
    // Start by defining the header with field keys needed for sorting.
    $header = array(
      array('data' => t('File name'), 'field' => 'file_name', 'sort' => 'asc'),
      array('data' => t('Translated'), 'field' => 'translated'),
      array('data' => t('Untranslated'), 'field' => 'untranslated'),
      array('data' => t('Not Allowed Translations'), 'field' => 'not_allowed_translations'),
      array('data' => t('Total Per File'), 'field' => 'total_per_file'),
    );
    // Get selected order from the request or the default one.
    $order = tablesort_get_order($header);
    // Get the field we sort by from the request if any.
    $sort = tablesort_get_sort($header);
    // Honor the requested sort.
    // Please note that we do not run any sql query against the database. The
    // 'sql' key is simply there for tabelesort needs.
    $rows = $this->getReportResultsSorted($order['sql'], $sort);

    // Display the sorted results.
    $display = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );
    return $display;
  }

  /**
   * Sort the results honoring the requested order.
   */
  public function getReportResultsSorted($order, $sort) {
    // get default sorted results.
    $results = $this->getReportResults();
    if (!empty($results)) {
      // Obtain the column we need to sort by.
      foreach ($results as $key => $value) {
        $order_column[$key] = $value[$order];
      }
      // Sort data.
      if ($sort == 'asc') {
        array_multisort($order_column, SORT_ASC, $results);
      }
      elseif ($sort == 'desc') {
        array_multisort($order_column, SORT_DESC, $results);
      }
      // Always place the 'totals' key at the end.
      $totals = $results['totals'];
      unset($results['totals']);
      $results['totals'] = $totals;
    }
    return $results;
  }

  /**
   * Update translation report counts.
   * @param String $translation.
   */
  public function translationReport($translation) {

    if (locale_string_is_safe($translation)) {
      if ($translation != '') {
        $this->SetTranslatedCount(1);
      }
      else {
        $this->SetUntranslatedCount(1);
      }
    }
    else {
      $this->SetNotAllowedTranslatedCount(1);
    }
    $this->SetTotalCount(1);
  }

  /**
   * Getter for translated_count.
   * @return Integer.
   */
  public function getTranslatedCount() {
    return $this->translated_count;
  }

  /**
   * Getter for untranslated_count.
   * @return Integer.
   */
  public function getUntranslatedCount() {
    return $this->untranslated_count;
  }

  /**
   * Getter for not_allowed_translated_count.
   * @return Integer.
   */
  public function getNotAllowedTranslatedCount() {
    return $this->not_allowed_translation_count;
  }

  /**
   * Getter for total_count.
   * @return Integer.
   */
  public function getTotalCount() {
    return $this->total_count;
  }

  /**
   * Getter for report_results.
   * @return Array.
   */
  public function getReportResults() {
    return $this->report_results;
  }

  /**
   * Setter for translated_count.
   * @param Integer $count.
   * @return Integer.
   */
  public function setTranslatedCount($count) {
    $this->translated_count += $count;
  }

  /**
   * Setter for untranslated_count.
   * @param Integer $count.
   * @return Integer.
   */
  public function setUntranslatedCount($count) {
    $this->untranslated_count += $count;
  }

  /**
   * Setter for not_allowed_translated_count.
   * @param Integer $count.
   * @return Integer.
   */
  public function setNotAllowedTranslatedCount($count) {
    $this->not_allowed_translation_count += $count;
  }

  /**
   * Setter for total_count.
   * @param Integer $count.
   * @return Integer.
   */
  public function setTotalCount($count) {
    $this->total_count += $count;
  }

  /**
   * Setter for report_results.
   *
   * Adds a new po file reports as a subarray to report_results.
   * @argument Array $new_array: array representing a row data.
   * @argument boolean $totals: TRUE when the row being added is the totals' one.
   */
  public function setReportResultsSubarray(array $new_array, $totals = FALSE) {
    if (!$totals) {
      $this->report_results[] = $new_array;
    }
    else {
      $this->report_results['totals'] = $new_array;
    }
  }

  /**
   * Add Totals row to results when there are some.
   */
  public function addTotalsRow() {
    $rows = $this->getReportResults();
    // Only adds total row when it is significant.
    if (!empty($rows)) {
      $total = array(
        'file_name' => format_plural(count($rows), 'One file', '@count files'),
        'translated' => 0,
        'untranslated' => 0,
        'not_allowed_translations' => 0,
        'total_per_file' => 0,
      );
      foreach ($rows as $row) {
        $total['translated'] += $row['translated'];
        $total['untranslated'] += $row['untranslated'];
        $total['not_allowed_translations'] += $row['not_allowed_translations'];
        $total['total_per_file'] += $row['total_per_file'];
      }
      $this->setReportResultsSubarray($total, TRUE);
    }
  }

}
