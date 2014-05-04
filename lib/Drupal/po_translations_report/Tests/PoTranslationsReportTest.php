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
      'name' => 'Po Translations Report functionality',
      'description' => 'Test Unit for module po_translations_report.',
      'group' => 'Po Translations Report',
    );
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('po_translations_report');

  /**
   * Tests po_translations_report results.
   */
  function testPoTranslationsReportResults() {
    $this->pass('pass');
  }

  /**
   * Tests Admin form.
   */
  function testPoTranslationsReportAdminForm() {
     // Create user with 'access po translations report' permission.
    $access_user = $this->drupalCreateUser(array('access po translations report', 'access administration pages'));
    $this->drupalLogin($access_user);
    $this->drupalPostForm('po_translations_report/settings/PoTranslationsReportAdmin', array(
      'folder_path' => $this->getDataPath(),
        ),
        t('Save configuration')
        );
    $this->assertText(t('The configuration options have been saved.'), 'Configure folder path');
  }

  /**
   * Gets data folder path that contains po test files.
   */
  function getDataPath() {
    $module_path = drupal_get_path('module', 'po_translations_report');
    $data_sub_path = '/lib/Drupal/po_translations_report/Tests/data';
    return DRUPAL_ROOT . '/' . $module_path . $data_sub_path;
  }

}
