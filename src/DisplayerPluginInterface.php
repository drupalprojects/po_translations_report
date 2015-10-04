<?php

namespace Drupal\po_translations_report;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Provides an interface for a plugin that displays results.
 *
 * @ingroup plugin_api
 */
interface DisplayerPluginInterface extends PluginFormInterface, ConfigurablePluginInterface {

  /**
   * Extract method.
   *
   * @param $results
   *   array of results to display.
   */
  public function display($results);

}
