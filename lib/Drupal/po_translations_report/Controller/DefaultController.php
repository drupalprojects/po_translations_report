<?php

namespace Drupal\po_translations_report\Controller;

use Drupal\Core\Controller\ControllerBase;

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
    foreach ($folder as $fileinfo) {
      if ($fileinfo->isFile() && $fileinfo->getExtension() == 'po') {
        // Flag we found at least one po file in this directory.
        $po_found = TRUE;
        //dpm($fileinfo->getFilename());
      }
    }
    // Handle the case where no po file could be found in the provided path.
    if(!$po_found){
      $message = t('No po was found in %folder', array('%folder' => $folder_path));
      drupal_set_message($message, 'warning');
    }
  }

}
