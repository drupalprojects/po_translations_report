<?php

/**
 * @file
 * Tests for po_translations_report.module.
 */

namespace Drupal\po_translations_report\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Url;

/**
 * Provides automated tests for the po_translations_report module.
 */
class PoTranslationsReportTest extends WebTestBase {

  /**
   * Defines the test.
   *
   * @return array
   *   array containing test information.
   */
  public static function getInfo() {
    return array(
      'name' => 'Po Translations Report functionality',
      'description' => 'Functionnal tests for module po_translations_report.',
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
  public function testPoTranslationsReportResults() {
    // Create user with 'access po translations report' permission.
    $permissions = array('access po translations report');
    $this->userCreateAndLogin($permissions);
    \Drupal::config('po_translations_report.admin_config')
        ->set('folder_path', $this->getDataPath())
        ->save();
    // Go to result page.
    $this->drupalGet('po_translations_report');
    $raw_assert = $this->getDefaultHTMLResults();
    $this->assertRaw($raw_assert, 'Expected html table results');
  }

  /**
   * Tests Admin form.
   */
  public function testPoTranslationsReportAdminForm() {
    // Create user with 'administer site configuration' permission.
    // 'access po translations report' permission is needed after redirection.
    $permissions = array(
      'administer site configuration',
      'access po translations report'
      );
    $this->userCreateAndLogin($permissions);
    $path = 'po_translations_report/settings/PoTranslationsReportAdmin';
    $this->drupalPostForm($path, array(
      'folder_path' => $this->getDataPath(),
        ), t('Save configuration')
    );
    // The form should redirect to po_translations_report page.
    $text_assert = t('Po Translations Report');
    $this->assertText($text_assert, 'Configure folder path');
  }

  /**
   * Test results per file for translate category.
   */
  public function testDetailsPerFileTranslated() {
    // Create user with 'access po translations report' permission.
    $permissions = array('access po translations report');
    $this->userCreateAndLogin($permissions);
    \Drupal::config('po_translations_report.admin_config')
        ->set('folder_path', $this->getDataPath())
        ->save();
    // Go to detail result page.
    $path = 'allowed_not_allowed.po/translated';
    $this->drupalGet('po_translations_report/' . $path);
    $source = 'Allowed HTML source string';
    $translation = 'Allowed HTML translation string';
    $raw_assert = '<td>' . $source . '</td>
                      <td>&lt;strong&gt;' . $translation . '&lt;/strong&gt;</td>';
    $this->assertRaw($raw_assert, 'Expected translated details results');
  }

  /**
   * Test results per file for untranslate category.
   */
  public function testDetailsPerFileUntranslated() {
    // Create user with 'access po translations report' permission.
    $permissions = array('access po translations report');
    $this->userCreateAndLogin($permissions);
    \Drupal::config('po_translations_report.admin_config')
        ->set('folder_path', $this->getDataPath())
        ->save();
    // Go to detail result page.
    $path = 'sample.po/untranslated';
    $this->drupalGet('po_translations_report/' . $path);
    $raw_assert = '<td>@count hours</td>
                      <td></td>';
    $this->assertRaw($raw_assert, 'Expected untranslated results');
  }

  /**
   * Test results per file for non allowed translation category.
   */
  public function testDetailsPerFileNonAllowedTranslations() {
    // Create user with 'access po translations report' permission.
    $permissions = array('access po translations report');
    $this->userCreateAndLogin($permissions);
    \Drupal::config('po_translations_report.admin_config')
        ->set('folder_path', $this->getDataPath())
        ->save();
    // Go to detail result page.
    $path = 'allowed_not_allowed.po/not_allowed_translations';
    $this->drupalGet('po_translations_report/' . $path);
    $source = 'Non allowed source string';
    $translation = 'Non allowed translation string should not be translated';
    $raw_assert = '<td>&lt;div&gt;' . $source . '&lt;/div&gt;</td>
                      <td>&lt;div&gt;' . $translation . '&lt;/div&gt;</td>';

    $this->assertRaw($raw_assert, 'Expected non allowed translations details');
  }

  /**
   * Test the results page in case of non configured module.
   */
  public function testNonConfiguredModuleCaseResults() {
    // Create user with 'access po translations report' permission.
    $permissions = array('access po translations report');
    $this->userCreateAndLogin($permissions);
    // Go to result page without configuring anything.
    $this->drupalGet('po_translations_report');
    $url_path = Url::fromRoute('po_translations_report.admin_form');
    $url = \Drupal::l(t('configuration page'), $url_path);
    $raw = t('Please configure a directory in !url.', array('!url' => $url));
    $this->assertRaw($raw, 'Expected result with no configuration');
  }

  /**
   * Test detailed result page in case of non configured module.
   */
  public function testNonConfiguredModuleCaseDetailsPageResult() {
    // Create user with 'access po translations report' permission.
    $permissions = array('access po translations report');
    $this->userCreateAndLogin($permissions);
    // Go to details result page without configuring anything.
    $file_name = 'sample.po';
    $this->drupalGet('po_translations_report/' . $file_name . '/translated');
    $raw = t('%file_name was not found', array('%file_name' => $file_name));
    $this->assertRaw($raw, 'Expected details result with no configuration');
  }

  /**
   * Create user with permissions and authenticate them.
   */
  public function userCreateAndLogin($permissions) {
    $access_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($access_user);
  }

  /**
   * Gets data folder path that contains po test files.
   */
  public function getDataPath() {
    $module_path = drupal_get_path('module', 'po_translations_report');
    $data_sub_path = '/src/Tests/data';
    return DRUPAL_ROOT . '/' . $module_path . $data_sub_path;
  }

  /**
   * Gets default html table results.
   */
  public function getDefaultHTMLResults() {
    return
        '<tbody>
              <tr class="odd">
                      <td>allowed_not_allowed.po</td>
                      <td><a href="/po_translations_report/allowed_not_allowed.po/translated">1</a></td>
                      <td>0</td>
                      <td><a href="/po_translations_report/allowed_not_allowed.po/not_allowed_translations">1</a></td>
                      <td>2</td>
                  </tr>
              <tr class="even">
                      <td>sample.po</td>
                      <td><a href="/po_translations_report/sample.po/translated">3</a></td>
                      <td><a href="/po_translations_report/sample.po/untranslated">1</a></td>
                      <td>0</td>
                      <td>4</td>
                  </tr>
              <tr class="odd">
                      <td>2 files</td>
                      <td>4</td>
                      <td>1</td>
                      <td>1</td>
                      <td>6</td>
                  </tr>
          </tbody>';
  }

}
