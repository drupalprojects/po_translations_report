Po Translations Report

Do you handle translations via po files and then import them to Drupal?

This module will help you to have reports on your po translations status.

Configure the path to a folder containing po files and you'll get some reports
on them.

Reports are made for the number of translated strings, number of untranstaled
strings, number of strings that are translatad but contain some non allowed
HTML (thus you will not be able to import it to the database), number of total
strings per a po file.

Checking that translations do not contain non allowed HTML uses the same
verifications [1] as Local module. This way, no surprises will occur when
importing po files with Local module.

A last row adds the totals for all po files in the folder.

Note that results are displayed in a table that is sortable by any column you
want. This uses Drupal Core tablesort.[2]

This module does not add any table to the database nor does it relay on the
database for its reports. It only does on fly reports reading po files.


REQUIREMENTS
------------

Local module is required to reuse the same checks for HTML Allowness.[1]

CONFIGURATION
-------------

Make sure to have 'access administration pages' permission to be able to
configure the module at the following path:
/po_translations_report/settings/PoTranslationsReportAdmin

Make sur to have 'access po translations report' permission to be able to access
reports at /po_translations_report

TESTS
-----
This module implements some functionnal tests that have the group name
'Po Translations Report'

[1] https://api.drupal.org/api/drupal/core!modules!locale!locale.module/function
/locale_string_is_safe/8
[2] https://api.drupal.org/api/drupal/core!includes!tablesort.inc/8
