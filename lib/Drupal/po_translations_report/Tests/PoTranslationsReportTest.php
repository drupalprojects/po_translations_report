<?php

/**
 * @file
 * Tests for po_translations_report.module.
 */

namespace Drupal\po_translations_report\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the po_translations_report module.
 */
class PoTranslationsReportTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'po_translations_report functionality',
      'description' => 'Test Unit for module po_translations_report.',
      'group' => 'Other',
    );
  }

  function setUp() {
    parent::setUp();
  }

  /**
   * Tests po_translations_report functionality.
   */
  function testpo_translations_report() {
    //Check that the basic functions of module po_translations_report.
    $this->assertEqual(TRUE, TRUE, 'Test Unit Generated via Console.');
  }

}
