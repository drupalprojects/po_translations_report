<?php

/**
 * @file
 * Contains \Drupal\po_translations_report\Controller\
 * PoTranslationsReportController.
 */

namespace Drupal\po_translations_report\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Gettext\PoStreamReader;

class PoTranslationsReportController extends ControllerBase {

  /**
   * Count of translated strings per file.
   * @var int
   */
  protected $translatedCount = 0;

  /**
   * Count of untranslated strings per file.
   * @var int
   */
  protected $untranslatedCount = 0;

  /**
   * Count of strings that contain non allowed HTML tags for translation.
   * @var int
   */
  protected $notAllowedTranslationCount = 0;

  /**
   * Count of strings per file.
   * @var int
   */
  protected $totalCount = 0;

  /**
   * Raw results in a form of a php array.
   * @var array
   */
  protected $reportResults = array();

  /**
   * Displays the report.
   * @return string
   *   HTML table for the results.
   */
  public function content() {
    $config = $this->config('po_translations_report.admin_config');
    $folder_path = $config->get('folder_path');
    // If nothing was configured, tell the user to configure the module.
    if ($folder_path == '') {
      $url_path = 'po_translations_report/settings/PoTranslationsReportAdmin';
      $url = l(t('configuration page'), $url_path);
      return t('Please configure a directory in !url.', array('!url' => $url));
    }
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
        $reader->open();
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

    return $this->render();
  }

  /**
   * Displays the results in a sortable table.
   * @see core/includes/sorttable.inc
   * @return array
   *   rendered array of results.
   */
  public function render() {
    // Get categories.
    $categories = $this->getAllowedDetailsCategries();
    // Start by defining the header with field keys needed for sorting.
    $header = array(
      array(
        'data' => t('File name'),
        'field' => 'file_name',
        'sort' => 'asc'),
      array(
        'data' => $categories['translated'],
        'field' => 'translated',
        ),
      array(
        'data' => $categories['untranslated'],
        'field' => 'untranslated',
        ),
      array(
        'data' => $categories['not_allowed_translations'],
        'field' => 'not_allowed_translations',
        ),
      array(
        'data' => t('Total Per File'),
        'field' => 'total_per_file',
        ),
    );
    // Get selected order from the request or the default one.
    $order = tablesort_get_order($header);
    // Get the field we sort by from the request if any.
    $sort = tablesort_get_sort($header);
    // Get default sorted results.
    $results = $this->getReportResults();
    // Honor the requested sort.
    // Please note that we do not run any sql query against the database. The
    // 'sql' key is simply there for tabelesort needs.
    $rows_sorted = $this->getResultsSorted($results, $order['sql'], $sort);
    $rows_linked = $this->linkifyResults($rows_sorted);

    // Display the sorted results.
    $display = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows_linked,
    );
    return $display;
  }

  /**
   * Link all figures to the dedicated details page.
   * @return array
   *   linkified array of results.
   */
  public function linkifyResults($results) {
    if (!empty($results)) {
      foreach ($results as $key => &$result) {
        if ($key !== 'totals') {
          if ($result['translated'] > 0) {
            $result['translated'] = l($result['translated'], 'po_translations_report/' . $result['file_name'] . '/translated');
          }
          if ($result['untranslated'] > 0) {
            $result['untranslated'] = l($result['untranslated'], 'po_translations_report/' . $result['file_name'] . '/untranslated');
          }
          if ($result['not_allowed_translations'] > 0) {
            $result['not_allowed_translations'] = l($result['not_allowed_translations'], 'po_translations_report/' . $result['file_name'] . '/not_allowed_translations');
          }
        }
      }
    }
    return $results;
  }

  /**
   * Sort the results honoring the requested order.
   * @param array $results
   * @param string $order
   * @param string $sort
   * @return array
   *   sorted array of results.
   */
  public function getResultsSorted($results, $order, $sort) {
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
      if (isset($results['totals'])) {
        $totals = $results['totals'];
        unset($results['totals']);
        $results['totals'] = $totals;
      }
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

  /**
   * Route title callback.
   *
   * @param string $file_name
   * @param string $category
   *
   * @return string
   *   The page title.
   */
  public function detailsTitle($file_name, $category) {
    // Get categories.
    $categories = $this->getAllowedDetailsCategries();
    if (in_array($category, array_keys($categories))) {
      // Get translated category label.
      $category = $categories[$category];
    }
    $title = $file_name . ' : [' . $category . ']';
    return Xss::filter($title);
  }

  /**
   * Displays string details per po file.
   * @return string
   *   HTML table of details.
   */
  public function details($file_name, $category) {
    $config = $this->config('po_translations_report.admin_config');
    $folder_path = $config->get('folder_path');
    $file = $folder_path . '/' . $file_name;
    $output = '';
    // Warn if file doesn't exist or the category is not known.
    if (!file_exists($file)) {
      $message = t('%file_name was not found', array('%file_name' => $file_name));
      drupal_set_message($message, 'error');
      return $output;
    }
    if (!in_array($category, array_keys($this->getAllowedDetailsCategries()))) {
      $message = t('%category is not a known category', array('%category' => $category));
      drupal_set_message($message, 'error');
      return $output;
    }
    $details_array = $this->getDetailsArray($file, $category);
    if (empty($details_array)) {
      return $output;
    }
    else {
      return $this->renderDetailsResults($details_array);
    }
  }

  /**
   * Get detailed array per a po file.
   * @param string $file
   * @param string $category
   * @return array $results
   */
  public function getDetailsArray($file, $category) {
    $reader = new PoStreamReader();
    $reader->setURI($file);
    $reader->open();
    $results = array();
    while ($item = $reader->readItem()) {
      // Singular case.
      if (!$item->isPlural()) {
        $source = $item->getSource();
        $translation = $item->getTranslation();
        $singular_results = $this->categorize($category, $source, $translation);
        $results = array_merge($results, $singular_results);
      }
      else {
        // Plural case.
        $plural = $item->getTranslation();
        foreach ($item->getSource() as $key => $source) {
          $translation = $plural[$key];
          $plural_results = $this->categorize($category, $source, $translation);
          $results = array_merge($results, $plural_results);
        }
      }
    }
    return $results;
  }

  /**
   * Helper method to categorize strings in a po file.
   * @param string $category
   * @param string $source
   * @param string $translation
   * @return array $results
   */
  public function categorize($category, $source, $translation) {
    $results = array();
    $safe_translation = locale_string_is_safe($translation);
    $translated = $translation != '';
    switch ($category) {
      case 'translated':
        if ($safe_translation && $translated) {
          $results[] = array(
            'source' => htmlentities($source),
            'translation' => htmlentities($translation),
          );
        }

        break;
      case 'untranslated':
        if ($safe_translation && !$translated) {
          $results[] = array(
            'source' => htmlentities($source),
            'translation' => htmlentities($translation),
          );
        }
        break;
      case 'not_allowed_translations':
        if (!$safe_translation) {
          $results[] = array(
            'source' => htmlentities($source),
            'translation' => htmlentities($translation),
          );
        }
        break;
    }
    return $results;
  }

  /**
   * Renders results in form of HTML table.
   * @param array $details_array
   * @return string
   */
  public function renderDetailsResults($details_array) {
    // Start by defining the header.
    $header = array(
      array('data' => t('Source'), 'field' => 'source', 'sort' => 'asc'),
      array('data' => t('Translation'), 'field' => 'translation'),
    );
    // Get selected order from the request or the default one.
    $order = tablesort_get_order($header);
    // Get the field we sort by from the request if any.
    $sort = tablesort_get_sort($header);
    // Honor the requested sort.
    // Please note that we do not run any sql query against the database. The
    // 'sql' key is simply there for tabelesort needs.
    $rows_sorted = $this->getResultsSorted($details_array, $order['sql'], $sort);

    // Display the details results.
    $display = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows_sorted,
    );

    return $display;
  }

  /**
   * Helper method to restore allowed categories.
   * @return array of allowed categories.
   */
  public function getAllowedDetailsCategries() {
    return array(
      'translated' => t('Translated'),
      'untranslated' => t('Untranslated'),
      'not_allowed_translations' => t('Not Allowed Translations'),
    );
  }

}
