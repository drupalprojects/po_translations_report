<?php

/**
 * @file
 * Contains \Drupal\po_translations_report\Controller\
 * PoTranslationsReportController.
 */

namespace Drupal\po_translations_report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Gettext\PoStreamReader;

class PoTranslationsReportController extends ControllerBase {

  protected $translatedCount = 0;
  protected $untranslatedCount = 0;
  protected $notAllowedTranslationCount = 0;
  protected $totalCount = 0;
  protected $reportResults = array();

  /**
   * Displays the report.
   * @return string
   *   HTML table for the results.
   */
  public function content() {
    $config = \Drupal::config('po_translations_report.admin_config');
    $folder_path = $config->get('folder_path');
    $folder = new \DirectoryIterator($folder_path);
    $po_found = FALSE;
    foreach ($folder as $fileinfo) {
      if ($fileinfo->isFile() && $fileinfo->getExtension() == 'po') {
        // Initialize reports for that file.
        $this->initializeCounts();
        // Flag we found at least one po file in this directory.
        $po_found = TRUE;
        // Instantiate and initialize the stream reader for this file.
        $reader = new PoStreamReader();
        $reader->setURI($fileinfo->getRealPath());

        try {
          $reader->open();
        }
        catch (\Exception $exception) {
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
   * @return array
   *   rendered array of results.
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
   * @return array
   *   sorted array of results.
   */
  public function getReportResultsSorted($order, $sort) {
    // Get default sorted results.
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
   *
   * @param string $translation
   *   contains the translated string.
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
   * Getter for translatedCount.
   * @return int
   *   translated count.
   */
  public function getTranslatedCount() {
    return $this->translatedCount;
  }

  /**
   * Getter for untranslatedCount.
   * @return int
   *   untranslated count.
   */
  public function getUntranslatedCount() {
    return $this->untranslatedCount;
  }

  /**
   * Getter for notAllowedTranslatedCount.
   * @return int
   *   not allowed translation count.
   */
  public function getNotAllowedTranslatedCount() {
    return $this->notAllowedTranslationCount;
  }

  /**
   * Getter for totalCount.
   * @return int
   *   total count.
   */
  public function getTotalCount() {
    return $this->totalCount;
  }

  /**
   * Getter for reportResults.
   * @return array
   *   reported results.
   */
  public function getReportResults() {
    return $this->reportResults;
  }

  /**
   * Setter for translatedCount.
   *
   * @param int $count
   *   the value to add to translated count.
   */
  public function setTranslatedCount($count) {
    $this->translatedCount += $count;
  }

  /**
   * Setter for untranslatedCount.
   *
   * @param int $count
   *   the value to add to untranslated count.
   */
  public function setUntranslatedCount($count) {
    $this->untranslatedCount += $count;
  }

  /**
   * Setter for notAllowedTranslatedCount.
   *
   * @param int $count
   *   the value to add to not allowed translated count.
   */
  public function setNotAllowedTranslatedCount($count) {
    $this->notAllowedTranslationCount += $count;
  }

  /**
   * Setter for totalCount.
   *
   * @param int $count
   *   the value to add to the total count.
   */
  public function setTotalCount($count) {
    $this->totalCount += $count;
  }

  /**
   * Setter for reportResults.
   *
   * Adds a new po file reports as a subarray to reportResults.
   *
   * @param array $new_array
   *   array representing a row data.
   * @param bool $totals
   *   TRUE when the row being added is the totals' one.
   */
  public function setReportResultsSubarray(array $new_array, $totals = FALSE) {
    if (!$totals) {
      $this->reportResults[] = $new_array;
    }
    else {
      $this->reportResults['totals'] = $new_array;
    }
  }

  /**
   * Adds totals row to results when there are some.
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

  /**
   * Initializes the counts to zero.
   */
  public function initializeCounts() {
    $this->translatedCount = 0;
    $this->untranslatedCount = 0;
    $this->notAllowedTranslationCount = 0;
    $this->totalCount = 0;
  }

}
