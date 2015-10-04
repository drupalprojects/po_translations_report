<?php

/**
 * @file
 * Contains \Drupal\po_translations_report\Controller\PoTranslationsReportController.
 */

namespace Drupal\po_translations_report\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\po_translations_report\PoReporter;
use Drupal\po_translations_report\PoDetailsReporter;

class PoTranslationsReportController extends ControllerBase {

  /**
   * Raw results in a form of a php array.
   *
   * @var array
   */
  protected $reportResults = array();

  /**
   * PoReporter service.
   *
   * @var Drupal\po_translations_report\PoReporter
   */
  protected $poReporter;

  /**
   * PoReporter service.
   *
   * @var Drupal\po_translations_report\PoDetailsReporter
   */
  protected $poDetailsReporter;

  /**
   * Constructor.
   *
   * @param PoReporter $poReporter
   */
  public function __construct(PoReporter $poReporter, PoDetailsReporter $poDetailsReporter) {
    $this->poReporter = $poReporter;
    $this->poDetailsReporter = $poDetailsReporter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('po_translations_report.po_reporter'), $container->get('po_translations_report.po_details_reporter')
    );
  }

  /**
   * Displays the report.
   *
   * @return string
   *   HTML table for the results.
   */
  public function content() {
    $config = $this->config('po_translations_report.admin_config');
    $folder_path = $config->get('folder_path');
    // If nothing was configured, tell the user to configure the module.
    if ($folder_path == '') {
      $url_path = Url::fromRoute('po_translations_report.admin_form');
      $url = \Drupal::l(t('configuration page'), $url_path);
      return array(
        '#type' => 'markup',
        '#markup' => t('Please configure a directory in !url.', array('!url' => $url)),
      );
    }
    $folder = new \DirectoryIterator($folder_path);
    $po_found = FALSE;
    foreach ($folder as $fileinfo) {
      if ($fileinfo->isFile() && $fileinfo->getExtension() == 'po') {
        $uri = $fileinfo->getRealPath();
        $subresults = $this->poReporter->PoReport($uri);
        $this->setReportResultsSubarray($subresults);
        // Flag we found at least one po file in this directory.
        $po_found = TRUE;
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
   *
   * @see core/includes/sorttable.inc
   * @return array
   *   Rendered array of results.
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
    $rows = $this->addCssClasses($rows_linked);

    // Display the sorted results.
    $display = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );
    return $display;
  }

  /**
   * Link all figures to the dedicated details page.
   *
   * @return array
   *   Sorted array of results.
   */
  public function linkifyResults($results) {
    if (!empty($results)) {
      foreach ($results as $key => &$result) {
        if ($key !== 'totals') {
          if ($result['translated'] > 0) {
            $route_params = array(
              'file_name' => $result['file_name'],
              'category' => 'translated',
            );
            $url_path = Url::fromRoute('po_translations_report.report_details', $route_params);
            $result['translated'] = \Drupal::l($result['translated'], $url_path);
          }
          if ($result['untranslated'] > 0) {
            $route_params = array(
              'file_name' => $result['file_name'],
              'category' => 'untranslated',
            );
            $url_path = Url::fromRoute('po_translations_report.report_details', $route_params);
            $result['untranslated'] = \Drupal::l($result['untranslated'], $url_path);
          }
          if ($result['not_allowed_translations'] > 0) {
            $route_params = array(
              'file_name' => $result['file_name'],
              'category' => 'not_allowed_translations',
            );
            $url_path = Url::fromRoute('po_translations_report.report_details', $route_params);
            $result['not_allowed_translations'] = \Drupal::l($result['not_allowed_translations'], $url_path);
          }
        }
      }
    }
    return $results;
  }

  /**
   * Adds css classes to results.
   *
   * @return array
   *   Linkified array of results.
   */
  public function addCssClasses($results) {
    if (!empty($results)) {
      foreach ($results as $key => &$result) {
        foreach ($result as $result_key => &$result_value) {
          $result_value = array(
            'data' => $result_value,
            'class' => $result_key,
          );
        }
      }
    }
    return $results;
  }

  /**
   * Sort the results honoring the requested order.
   *
   * @param array $results
   *   Array of results.
   * @param string $order
   *   The asked order.
   * @param string $sort
   *   The wanted sort.
   *
   * @return array
   *   Array of results.
   */
  public function getResultsSorted(array $results, $order, $sort) {
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
   * Getter for reportResults.
   *
   * @return array
   *   Reported results.
   */
  public function getReportResults() {
    return $this->reportResults;
  }

  /**
   * Adds a new po file reports as a subarray to reportResults.
   *
   * @param array $new_array
   *   Array representing a row data.
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
        'file_name' => \Drupal::translation()->formatPlural(count($rows), 'One file', '@count files'),
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
   * Route title callback.
   *
   * @param string $file_name
   *   The file name.
   * @param string $category
   *   The category.
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
   *
   * @return string
   *   HTML table of details.
   */
  public function details($file_name, $category) {
    $config = $this->config('po_translations_report.admin_config');
    $folder_path = $config->get('folder_path');
    $filepath = $folder_path . '/' . $file_name;
    $output = '';
    // Warn if file doesn't exist or the category is not known.
    if (!file_exists($filepath)) {
      $message = t('%file_name was not found', array('%file_name' => $file_name));
      drupal_set_message($message, 'error');
      return array(
        '#type' => 'markup',
        '#markup' => $output,
      );
    }
    if (!in_array($category, array_keys($this->getAllowedDetailsCategries()))) {
      $message = t('%category is not a known category', array('%category' => $category));
      drupal_set_message($message, 'error');
      return array(
        '#type' => 'markup',
        '#markup' => $output,
      );
    }
    $details_array = $this->poDetailsReporter->poReportDetails($filepath, $category);
    if (empty($details_array)) {
      return array(
        '#type' => 'markup',
        '#markup' => $output,
      );
    }
    else {
      return $this->renderDetailsResults($details_array);
    }
  }

  /**
   * Renders results in form of HTML table.
   *
   * @param array $details_array
   *   Array of details per po file.
   *
   * @return string
   *   HTML table represented results.
   */
  public function renderDetailsResults(array $details_array) {
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
    // 'sql' key is simply there for tablesort needs.
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
   *
   * @return array
   *   Array of allowed categories.
   */
  public function getAllowedDetailsCategries() {
    return array(
      'translated' => t('Translated'),
      'untranslated' => t('Untranslated'),
      'not_allowed_translations' => t('Not Allowed Translations'),
    );
  }

}
