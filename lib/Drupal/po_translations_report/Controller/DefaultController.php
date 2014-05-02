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
//            if (locale_string_is_safe($item->getTranslation())) {
//              if ($item->getTranslation() != '') {
//                $translated_count++;
//              }
//              else {
//                $untranslated_count++;
//              }
//            }
//            else {
//              $not_allowed_translation_count++;
//            }
//            $total_count++;
          }
          else {
            // Plural case.
            $plural = $item->getTranslation();
            foreach ($item->getSource() as $key => $source) {

              $this->translationReport($plural[$key]);
//              if (locale_string_is_safe($plural[$key])) {
//                if ($plural[$key] != '') {
//                  $translated_count++;
//                }
//                else {
//                  $untranslated_count++;
//                }
//              }
//              else {
//                $not_allowed_translation_count++;
//              }
//              $total_count++;
            }
          }
        }
        $display .= '<br />';
        $display .= $file_name . '__' . $this->translated_count . '__' . $this->untranslated_count . '__' . $this->not_allowed_translation_count . '__' . $this->total_count;
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

  public function translationReport($translation) {

    if (locale_string_is_safe($translation)) {
      if ($translation != '') {
        $this->translated_count++;
      }
      else {
        $this->untranslated_count++;
      }
    }
    else {
      $this->not_allowed_translation_count++;
    }
    $this->total_count++;
  }

}
