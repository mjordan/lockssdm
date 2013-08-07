<?php

/**
 * @file
 * Include file containing code used by both generate_aus.php and index.php.
 * Can also be run from the command line to generate manifest files for
 * uploading to the CONTENTdm server. Usage: php ./aus.php
 *
 * Copyright 2013 Mark Jordan (mjordan@sfu.ca).
 * 
 * Licensed under the MIT License; see MIT-LICENSE.txt for details.
 */

include_once('config.php');
include_once('common.php');

/**
 * Main router.
 */
if ($cli_mode == FALSE) {
  $url_args = Zend_Registry::get('url_args');
}
else {
  $url_arge = NULL;
}

if ($cli_mode == FALSE && $url_args[0] == 'aus') {
  if ($static_manifests) {
    generate_static_manifests_list();
  }
  else {
    generate_dynamic_manifests_list();
  }
}
elseif ($cli_mode == FALSE && $static_manifests == FALSE && $url_args[0] == 'manifest') {
  generate_au_manifest($url_args[1], $url_args[2]);
}
elseif ($cli_mode == FALSE && $static_manifests == TRUE && $url_args[0] == 'manifest') {
  read_au_manifest($url_args[1], $url_args[2]);
}
elseif ($cli_mode == TRUE) {
  if ($static_manifests == FALSE) {
    print "The 'static manifests' option in your config file is set to FALSE; you need to change it to TRUE and retun this script.\n";
    exit;
  }
  generate_au_manifest_files();
}
else {
  print "Error from aus.php: Action not recognized";
  exit;
}

/**
 * Functions.
 */

/**
 * Generate a list of AUs for each collection.
 */
function generate_dynamic_manifests_list() {
  $collections_to_harvest = Zend_Registry::get('collections_to_harvest');
  $aliases = array_keys($collections_to_harvest);
  $self = Zend_Registry::get('self');
  // $output .= Zend_Registry::get('lockssdm_disclaimer');
  $output .= '<div class="lockssdm_au_list">';
  if (count($aliases)) {
    $output .= "<ul>\n";
    foreach ($aliases as $alias) {
      $result = query_contentdm($alias);
      $au_links .= generate_collection_aus($alias, $result['pager']['total'], FALSE);
    }
    $output .= $au_links;
    $output .= "</ul>\n";
  }
  $output .= '</div>';
  print $output;
}

/**
 * Generate the list of static manifest files.
 */
function generate_static_manifests_list() {
  $collections_to_harvest = Zend_Registry::get('collections_to_harvest');
  $aliases = array_keys($collections_to_harvest);
  $self = Zend_Registry::get('self');
  $manifest_files_dir = Zend_Registry::get('manifest_files_dir');
  // $output .= Zend_Registry::get('lockssdm_disclaimer');
  $glob_pattern = $manifest_files_dir . 'manifest_*.html';
  $manifest_files = glob($glob_pattern);
  $output .= '<div class="lockssdm_au_list">';
  $output .= '<ul>';  
  foreach ($manifest_files as $manifest_file) {
    $pathinfo = pathinfo($manifest_file);
    $filename = preg_replace('/^manifest_/', '', $pathinfo['filename']);
    preg_match('/\d+$/', $filename, $matches);
    $au_num = $matches[0];
    $alias = '/' . preg_replace('/_\d+$/', '', $filename);
    $url = $self . 'manifest' . $alias . '/' . $au_num;
    $label = $collections_to_harvest[$alias] . ' (AU ' . $au_num . ')';
    $output .= '<li><a href="' . $url . '">' . $label . "</a></li>\n";
  }
  $output .= '</ul>';
  $output .= '</div>';
  print $output;  
}

/**
 * Invoked only in CLI mode. Loop through all collection and write out
 * the information that defines the LOCKSS manifest files for each collection,
 * to files named alias_aunum.html.
 */
function generate_au_manifest_files() {
  global $collections_to_harvest;
  $aliases = array_keys($collections_to_harvest);
  if (count($aliases)) {
    foreach ($aliases as $alias) {
      $result = query_contentdm($alias);
      generate_collection_aus($alias, $result['pager']['total'], TRUE);
    }
  }
}

/**
 * For the designated collection, loop through all records and define the
 * AUs for that collection. Used in both CLI mode and in dynamic mode.
 */
function generate_collection_aus($alias, $total_records, $cli_mode) {
  if ($cli_mode) {
    global $self;
    global $chunk_size;
    global $max_au_file_count;
    global $collections_to_harvest;     
  }
  else {
    $self = Zend_Registry::get('self');
    $chunk_size = Zend_Registry::get('chunk_size');
    $max_au_file_count = Zend_Registry::get('max_au_file_count');
    $collections_to_harvest = Zend_Registry::get('collections_to_harvest');
  }
  $au_list = array();
  $total_files = 0;
  $au_count = 0;
  $au_num = '1';
  $au_list[] = array('au_num' => $au_num, 'alias' => $alias, 'start_at' => '1');
  $rec_count = 0;

  // Generate a list of all records so we can then iterate through them 
  // and create the AUs.
  $all_items = array();
  $num_queries = (int) $total_records / (int) $chunk_size;
  $rounded_num_queries = ceil($num_queries);

  $all_records = array();
  for ($i = 0; $i <= $rounded_num_queries; $i++) {
    $start_at = ($i == 0) ? ($start_at = 1) : ($start_at = $i * $chunk_size + 1);
    if ($start_at < $total_records) {
      $query_results = query_contentdm($alias, $start_at);
      foreach ($query_results['records'] as $record) {
        $all_records[] = $record;
      }
    }
  }
  $record_count = count($all_records);

  // At this point we have a list of all the records in a collection. 
  // We now need to loop through this list and create AU URLs expressing
  // $chunk_size or less each having fewer than $max_au_file_count files.
  $record_num = 0;
  foreach ($all_records as $record) {
    $record_num++;
    if ($record['filetype'] != 'cpd')  {
      // Web page, thumbnail, image file each count as 1 file.
      $file_count = 3;
    }
    else {
      $file_count = count_items_children($alias, $record['pointer']);
      // Children, web page, thumbnail.
      $file_count = $file_count + 2;
    }
    $total_files = $total_files + $file_count;

    // Check for total files first, then record number. 
    if (($total_files >= $max_au_file_count) || ($record_num >= $chunk_size)) {
      $au_num++;
      // Assemble an entry in the list of AUs to be generated.
      $au_list[] = array('au_num' => $au_num, 'alias' => $alias, 'start_at' => $record_num);
      // Reset file counter since we're starting a new AU.
      $total_files = 0;
      // Reset record counter since we're starting a new AU.
      $record_num = 0;
    }  
  }
  
  if ($cli_mode) {
    // In CLI mode, write out the manifest files for each collection,
    // using the naming convention alias_aunum.html.
    foreach ($au_list as $au) {
      $alias = trim($au['alias'], '/');
      $au_filename = getcwd() . DIRECTORY_SEPARATOR . 'manifest_' . $alias . '_' . $au['au_num'] . '.html';
      print "Generating $au_filename\n";
      $manifest_content = generate_au_manifest($alias, $au['au_num'], $au['start_at']);
      file_put_contents($au_filename, $manifest_content);
    }
  }
  else {
    // In realtime mode, generate the links to each AU for the current collection.
    $au_links = '';
    foreach ($au_list as $au) {
      $url = $self . 'manifest' . $au['alias'] . '/' . $au['au_num'];
      $label = $collections_to_harvest[$alias] . ' (AU ' . $au['au_num'] . ')';
      $au_links .= '<li><a href="' . $url . '">' . $label . "</a></li>\n";
    }
    return $au_links;
  }
}

/**
 * Generate the AUs from the items in the $aus array. Is used to in both
 * CLI mode (to write each manifest out to a file for uploading) and in
 * realtime mode (to generate each manifest dynamically).
 */
function generate_au_manifest($alias, $au_num) {
  global $cli_mode;
  $alias = '/' . $alias;
  if ($cli_mode) {
    global $self;
    global $permission_statement;
    global $collections_to_harvest;
    global $chunk_size;
    global $lockssdm_footer;
  }
  else {
    $self = Zend_Registry::get('self');
    $permission_statement = Zend_Registry::get('lockssdm_permission_statement');
    $collections_to_harvest = Zend_Registry::get('collections_to_harvest');
    $chunk_size = Zend_Registry::get('chunk_size');
    $lockssdm_footer = Zend_Registry::get('lockssdm_footer');
  }
  $self = rtrim($self, '/');
  
  if ($au_num == 1) {
    $start_at = '1';
  }
  else {
    // The first AU takes care of the first chunck size; we also want to start
    // every AU at the first item in the next chunk.
    $start_at = ($au_num - 1) * $chunk_size + 1;
  }
  
  $output = $permission_statement;  
  $output .= '<div class="lockssdm_item_list"><ul>';

  // Generate the manifest dynmically (or if in CLI mode, generate the
  // manifest file content for writing to disk and return it to 
  // generate_au_manifest_files()).
  $query_results = query_contentdm($alias, $start_at);
  foreach ($query_results['records'] as $record) {
    $item_info = get_item_info($alias, $record['pointer']);
    $url = $self . '/display' . $alias . '/' . $record['pointer'];
    $output .= '<li><a href="' . $url . '">' . $item_info['title'] . "</a></li>\n";
  }
    
  $output .= '</ul></div>';
  $output .= $lockssdm_footer;
  
  if ($cli_mode) {
    return $output;
  }
  else {
    print $output;
  }
}

/**
 * Invoked only when $static_manifests is TRUE. Read the manifest file
 * and print it out to the web page.
 */
function read_au_manifest($alias, $au_num) {
  $permission_statement = Zend_Registry::get('lockssdm_permission_statement');
  $output = $permission_statement;  
  // Read the manifest file and include it in $output.
  $manifest_files_dir = Zend_Registry::get('manifest_files_dir');
  $manifest_file = $manifest_files_dir . 'manifest_' . $alias . '_' . $au_num . '.html';
  $output .= file_get_contents($manifest_file);
  print $output;
}

/**
 * Query (i.e., browse) CONTENTdm for a collection and return an array of records.
 */
function query_contentdm($alias, $start_at = 1) {
  global $cli_mode;
  if (!preg_match('/^\//', $alias)) {
    $alias = '/' . $alias;
  }
  if ($cli_mode) {
    global $ws_url;
    global $error_log;
    global $chunk_size;
  }
  else {
    $ws_url = Zend_Registry::get('ws_url');
    $error_log = Zend_Registry::get('error_log');
    $chunk_size = Zend_Registry::get('chunk_size');
  }
  $qm = array(
    'alias' => $alias,
    'searchstrings' => '0',
    // We ask for as little possible info at this point since we'll be
    // doing another query on each item later.
    'fields' => 'dmcreated',
    'sortby' => 'dmcreated!dmrecord',
    'maxrecs' => $chunk_size,
    'start' => $start_at,
    // We only want top-level items, not pages.
    'supress' => 1,
    'docptr' => 0,
    'suggest' => 0,
    'facets' => 0,
    'format' => 'json'
  );

  $query = $ws_url . 'dmQuery'. $qm['alias'] . '/'. $qm['searchstrings'] . 
    '/'. $qm['fields'] . '/'. $qm['sortby'] . '/'. $qm['maxrecs'] . '/'. 
    $start_at . '/'. $qm['supress'] . '/'. $qm['docptr'] . '/'. $qm['suggest'] . 
    '/'. $qm['facets'] . '/' . $qm['format'];
    
  // Query CONTENTdm and return records.
  if ($json = file_get_contents($query, false, NULL)) {
    return json_decode($json, true);
  } else {
    $message = date('c') . "\t". 'Query failed:' . "\t" . $query . "\n";
    return FALSE;
  }
}

/**
 * Count the current item's children.
 */
function count_items_children($alias, $pointer) {
  global $cli_mode;
  if ($cli_mode) {
    global $skip_files_pattern;   
  }
  else {
    $skip_files_pattern = Zend_Registry::get('skip_files_pattern');
  }
  $structure = get_compound_object_info($alias, $pointer);
  // i.e., Web services API says this item is not compound.
  $structure = simplexml_load_string($structure);
  if (is_object($structure)) {
    $pages = $structure->xpath('//page');
  }
  return count($pages);
}

?>
