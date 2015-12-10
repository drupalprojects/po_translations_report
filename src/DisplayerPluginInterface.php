<?php

/**
 * @file
 * Contains \Drupal\po_translations_report\DisplayerPluginInterface.
 */

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
   * @param array $results
   *   array of results to display.
   */
  public function display($results);

}
