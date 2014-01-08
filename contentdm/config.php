<?php
 
/**
 * @file
 * Include file containing configuration variables used by both index.php
 * and generate_aus.php.
 * 
 * Licensed under the MIT License; see MIT-LICENSE.txt for details.
 */
 
/**
 * Set this to TRUE if you are generating LOCKSS manifests but running aus.php
 * at the command line and uploading the manifest files. Set to FALSE if you
 * are letting LOCKSSdm generate manifest files dynamically. See README.txt for
 * more information.
 */
$static_manifests = TRUE;

/**
 * Change this variable to your CONTENTdm server's hostname, without the trailing slash.
 */
$server = 'http://contentdmserver.yourlibrary.net';

/**
 * Change this variable to your CONTENTdm website's hostname, without the trailing slash.
 */
$website = 'http://contentdmwebsite.yourlibrary.net';

/**
 * Change this variable to the subdirectory you created in the CONTENTdm 'Custom
 * pages' tool. Do not include either the leading or trailing slash.
 */
$lockssdm_directory = 'lockssdm';

/**
 * Change this variable to the path where you upload files in CONTENTdm's 'Custom
 * pages' tool. This path can be found at the top of the "Upload a file..." dialog box.
 * Be sure to include the trailing slash. You only need to set this if $static_manifests,
 * above, is set to TRUE.
 */
$manifest_files_dir = '/usr/local/Content6/Website/public_html/ui/custom/default/collection/default/resources/custompages/lockssdm/';

/**
 * Change this variable to an array of collections you want LOCKSS to harvest, using
 * collection 'aliases' as the keys and the collection's names as the values. Aliases
 * must start with a '/'.
 */
$collections_to_harvest = array(
  '/foo' => 'Collection Foo',
  '/bar' => 'Collection Bar',
  '/fob' => 'Collection Fob',
  '/baz' => 'Collection Baz',
);

/**
 * You should not have to change any variables below this line.
 */

// Paths to various CONTENTdm utilities.
$ws_url = $server . ':81/dmwebservices/index.php?q=';
$self = $website. '/cdm/' . $lockssdm_directory;
$thumbnail_url = $website . '/utils/getthumbnail/collection';
$getimage_url = $website . '/utils/ajaxhelper/';
$getfile_url = $website . '/utils/getfile/collection';
$reference_url_base = $website . '/u/?';
// Error log file.
$error_log = '/tmp/lockssdm_error_log.txt';
// Variable log file.
$log_var_file = '/tmp/lockssdm_var_log.txt';
// Text to show end users if they see a preserved item or AU list.
// $lockssdm_disclaimer = '<div class="lockssdm_disclaimer">You are viewing a simplified, backup-friendly version of our CONTENTdm collections</div>';
// LOCKSS permission statement. Do not change unless advised to by LOCKSS staff.
$lockssdm_permission_statement = '<div><img src="http://www.lockss.org/images/LOCKSS-small.gif" height="108" width="108">' . "\n" .
  'LOCKSS system has permission to collect, preserve, and serve this Archival Unit</div>' . "\n";
// Footer to show on pages produced by lockssdm.
$lockssdm_footer = '<div class="lockssdm_footer">Powered by <a href="http://www.contentdm.org/">CONTENTdm&reg;</a>, preserved by <a href="http://www.lockss.org/">LOCKSS</a>.</div>';
// User agent string used by the LOCKSS crawler.
$lockss_user_agent = 'LOCKSS cache';
// Maximum number of files allowed in an AU. Default is 50000.
$max_au_file_count = 50000;
// Don't change $chunk_size unless CONTENTdm is timing out. Default is 1000.
$chunk_size = 1000;
// Don't change $start_at unless from 1 you are exporting a range of records.
// If you want to export a range, use the number of the first record in the range.
$start_at = '1'; 
// The last record in subset, not the entire record set. Don't change 
// $last_rec from 0 unless you are exporting a subset of records. If you
// want to export a range, use the number of records in the subset, e.g., 
// if you want to export 200 records, use that value.
$last_rec = '0';
// Don't display these fields in the item records.
$admin_fields = array('fullrs', 'find', 'dmaccess', 'dmimage', 'dmcreated', 'dmmodified', 'dmoclcno', 'dmrecord');
// Extensions of files to not display (like files that were originally named Thumbs.db).
$skip_files_pattern = '/\.db$/i';

// If we're running in an HTTP envrionment, include the Zend Registry equivalents
// to the above config values.
if (isset($_SERVER["REQUEST_URI"])) {
  $cli_mode = FALSE;
  require_once('config_zend.php');
}
else {
  $cli_mode = TRUE;
}

?>
