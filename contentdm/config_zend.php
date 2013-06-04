<?php

/**
 * @file
 * Include file containing Zend Registray versions of configuration variables 
 * defined in config.php. You should not need to change this file.
 *
 * Copyright 2013 Mark Jordan (mjordan@sfu.ca).
 * 
 * Licensed under the MIT License; see MIT-LICENSE.txt for details.
 */

/**
 * Initialize Zend Registry versions of the configuration variables.
 */
if ($cli_mode == FALSE) {
  Zend_Registry::set('static_manifests', $static_manifests);
  Zend_Registry::set('manifest_files_dir', $manifest_files_dir);
  Zend_Registry::set('collections_to_harvest', $collections_to_harvest);
  Zend_Registry::set('ws_url', $ws_url);
  Zend_Registry::set('thumbnail_url', $thumbnail_url);
  Zend_Registry::set('getimage_url', $getimage_url);
  Zend_Registry::set('getfile_url', $getfile_url);
  Zend_Registry::set('reference_url_base', $reference_url_base);
  Zend_Registry::set('error_log', $error_log);
  Zend_Registry::set('log_var_file', $log_var_file);
  // Zend_Registry::set('lockssdm_disclaimer', $lockssdm_disclaimer);
  Zend_Registry::set('lockssdm_permission_statement', $lockssdm_permission_statement);
  Zend_Registry::set('lockssdm_footer', $lockssdm_footer);
  Zend_Registry::set('lockss_user_agent', $lockss_user_agent);
  Zend_Registry::set('max_au_file_count', $max_au_file_count);
  Zend_Registry::set('chunk_size', $chunk_size);
  Zend_Registry::set('start_at', $start_at); 
  Zend_Registry::set('last_rec', $last_rec);
  Zend_Registry::set('admin_fields', $admin_fields);
  Zend_Registry::set('skip_files_pattern', $skip_files_pattern);
}

/**
 * Set up URL arguments for use in an HTTP environment.
 */
if ($cli_mode == FALSE) {
  $url_args = explode('/', $_SERVER["REQUEST_URI"]);
  // Get rid of the first three URL arguments ('', 'cdm', and 'lockssdm')
  // since we're only interested in the arguments that come after those.
  Zend_Registry::set('url_args', array_slice($url_args, 3));

  // Define this script's base URL. $request_parts[1] should be 'cdm' and $request_parts[2]
  // hould be the script's directory.
  $request_parts = explode('/', $_SERVER["REQUEST_URI"]);
  Zend_Registry::set('self', $server . '/' . $request_parts[1] . '/' . $request_parts[2] . '/');
}

?>
