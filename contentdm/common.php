 <?php

/**
 * @file
 * Include file containing code used by both generate_aus.php and index.php.
 *
 * Copyright 2013 Mark Jordan (mjordan@sfu.ca).
 * 
 * Licensed under the MIT License; see MIT-LICENSE.txt for details.
 */

/**
 * Get the compound item's structure from the CONTENTdm API.
 */
function get_compound_object_info($alias, $pointer) {
  global $cli_mode;
  if ($cli_mode) {
    global $ws_url;
  }
  else {
    $ws_url = Zend_Registry::get('ws_url');
  }
  $request = $ws_url . 'dmGetCompoundObjectInfo'. $alias . '/'. $pointer . '/xml';
  // Native API calls expected XML from dmGetCompoundObjectInfo,
  // so we just return the raw XML.
  return file_get_contents($request, false, NULL);
}

/**
 * Get the item's metadata from the CONTENTdm API.
 */
function get_item_info($alias, $pointer) {
  global $cli_mode;
  if ($cli_mode) {
    global $ws_url;
    global $error_log;
  }
  else {
    $ws_url = Zend_Registry::get('ws_url');
    $error_log = Zend_Registry::get('error_log');   
  }
  $request = $ws_url .'dmGetItemInfo'. $alias . '/'. $pointer . '/json';
  $json = file_get_contents($request, false, NULL);
  $result = json_decode($json, TRUE);
  return $result;
}

/**
 * Log a variable's value to a file. Adapted from the Drupal devel
 * module's dd() function.
 */
function log_var($data, $label = NULL) {
  if ($cli_mode) {
    global $log_var_file;
  }
  else {
    $log_var_file = Zend_Registry::get('log_var_file');
  }
  ob_start();
  print_r($data);
  $string = ob_get_clean();
  if ($label) {
    $out = $label .': '. $string;
  }
  else {
    $out = $string;
  }
  $out .= "\n";
  if (file_put_contents($log_var_file, $out, FILE_APPEND) === FALSE) {
    return FALSE;
  }
}
