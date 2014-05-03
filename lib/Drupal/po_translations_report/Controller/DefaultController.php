<?php

namespace Drupal\po_translations_report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Gettext\PoStreamReader;

class DefaultController extends ControllerBase {

  private $translated_count = 0;
  private $untranslated_count = 0;
  private $not_allowed_translation_count = 0;
  private $total_count = 0;

  /**
   * content
   * @return string
   */
  public function content() {
    $config = \Drupal::config('po_translations_report.admin_config');
    $folder_path = $config->get('folder_path');
    $folder = new \DirectoryIterator($folder_path);
    $po_found = FALSE;
    $file_name = '';
    //$translated_count = $untranslated_count = $not_allowed_translation_count = $total_count = 0;
    $display = t('File name') . '__' . t('translated') . '__' . t('Untranslated') . '__' . t('not_allowed translation') . '__' . t('Total');
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
            $file_name = $fileinfo->getFilename();
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
        $display .= '<br />';
        $display .= $file_name . '__' . $this->getTranslatedCount() . '__' . $this->getUntranslatedCount() . '__' . $this->getNotAllowedTranslatedCount() . '__' . $this->getTotalCount();
      }

      // Handle the case where no po file could be found in the provided path.
      if (!$po_found) {
        $message = t('No po was found in %folder', array('%folder' => $folder_path));
        drupal_set_message($message, 'warning');
      }
    }
    // Display the results.
    return $display;
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

}
