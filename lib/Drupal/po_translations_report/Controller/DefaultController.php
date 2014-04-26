<?php

namespace Drupal\po_translations_report\Controller;

use Drupal\Core\Controller\ControllerBase;

class DefaultController extends ControllerBase 
{

  /**
   * content
   * @return string
   */
  public function content() {
    $config = $this->config('po_translations_report.admin_config');
    return $config->get('folder_path');
  }
}
