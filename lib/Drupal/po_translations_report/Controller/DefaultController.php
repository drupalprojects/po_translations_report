<?php

namespace Drupal\po_translations_report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Gettext\PoStreamReader;

class DefaultController extends ControllerBase {

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
    $translated_count = $untranslated_count = $not_allowed_translation_count = $total_count = 0;
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
            if (locale_string_is_safe($item->getTranslation())) {
              if ($item->getTranslation() != '') {
                $translated_count++;
              }
              else {
                $untranslated_count++;
              }
            }
            else {
              $not_allowed_translation_count++;
            }
            $total_count++;
          }
        }
        $display .= '<br />';
        $display .= $file_name . '__' . $translated_count . '__' . $untranslated_count . '__' . $not_allowed_translation_count . '__' . $total_count;
      }

      // Handle the case where no po file could be found in the provided path.
      if (!$po_found) {
        $message = t('No po was found in %folder', array('%folder' => $folder_path));
        drupal_set_message($message, 'warning');
      }
    }
    // Display the results.
    return  $display;
  }

}
