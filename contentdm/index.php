<?php

/**
 * @file
 * Main script for LOCKSSdm, the LOCKSS front-end plugin for CONTENTdm 6.
 * Even though CONTENTdm 6 plugins operate in the Zend Framework environment, 
 * this plugin does not follow most Zend coding practices other than use 
 * of the Zend Registry to manage configuration variables.
 *
 * Copyright 2013 Mark Jordan (mjordan@sfu.ca).
 * 
 * Licensed under the MIT License; see MIT-LICENSE.txt for details.
 */

include('config.php');
include('common.php');

$url_args = Zend_Registry::get('url_args');

/**
 * Main router.
 */
if ($url_args[0] == 'aus') {
  include('aus.php');
}
elseif ($url_args[0] == 'manifest') {
  include('aus.php');
}
elseif ($url_args[0] == 'display') {
  $url_args[1] = '/' . $url_args[1];
  render_item($url_args[1], $url_args[2]);
}
else {
  print "Error from index.php: Expected action not found";
  exit;
}

/**
 * Functions.
 */

/**
 * Render the current item.
 */
function render_item($alias, $pointer) {
  $ws_url = Zend_Registry::get('ws_url');
  $error_log = Zend_Registry::get('error_log');
  // $output .= Zend_Registry::get('lockssdm_disclaimer');
  $item_info = get_item_info($alias, $pointer);
  // Format the item metadata.
  $output .= generate_metadata_tags($alias, $item_info);
  $output .= render_item_template($alias, $pointer, $item_info);
  $output .= Zend_Registry::get('lockssdm_footer');
  print $output;
}

/**
 * Provide a simple 'template' for the current item. Wraps content to be
 * preserved in a div of class="lockssdm_content_wrapper".
 */
function render_item_template($alias, $pointer, $item_info) {
  $self = Zend_Registry::get('self');
  $output .= '<div class="lockssdm_content_wrapper">'. "\n";
  $output .= '<h2>' . $item_info['title'] . '</h2>' . "\n";
  
  // Check to see if the item is a child item, and if so, display a link
  // to its parent.
  $parent_pointer = get_parent_item($alias, $pointer);
  if ($parent_pointer != -1) {
	$parent_item_info = get_item_info($alias, $parent_pointer);
	$url = $self . 'display' . $alias . '/' . $parent_pointer;
    $output .= '<div class="lockssdm_link_to_parent">This item is part of <a href="' . 
      $url . '">' . $parent_item_info['title'] . '</a></div>' . "\n";
  }
  
  $output .= create_thumbnail_link($alias, $pointer, $item_info['find']);
  $filtered_metadata = filter_metadata($alias, $item_info);
  foreach ($filtered_metadata as $field_name => $field_value) {
    if (is_string($field_value)) {
      $output .= '<div class="lockssdm_metadata_field"><strong>' . $field_name . 
        '</strong>: ' . $field_value . '</div>' . "\n";
    }
  }
  // If the current item is a compound item, display links to children 
  // below the metadata.
  if (preg_match('/cpd$/', $item_info['find'])) {
      $output .= render_items_children($alias, $pointer);
  }
  $output .= '<div>'. "\n";
  return $output;
}

/**
 * Creates a link (using the item's thumbnail as he hyperlink) to the 
 * item's file.
 * -For image-based items, the link points to CONTENTdm's GetImage utility, 
 *  displaying the largest version of the image available.
 * -For compound items, doesn't display a link since the child page 
 *  thumbnails are displayed separately.
 * -For all other items, the link points to GetFile.
 */
function create_thumbnail_link($alias, $pointer, $filename) {
  $thumbnail_url = Zend_Registry::get('thumbnail_url');
  $getimage_url = Zend_Registry::get('getimage_url');
  $getfile_url = Zend_Registry::get('getfile_url');
  $output .= '<div class="lockssdm_item_thumbnail">';
  switch(TRUE) {
    case (preg_match('/cpd$/i', $filename)):
      $output .= '';
      break;
    case (preg_match('/(jpg|jpeg|jp2|gif|png)/i', $filename)):
      $image_info = get_image_info($alias, $pointer);
      $output .= '<a href="' . $getimage_url .  '?CISOROOT=' . 
        $alias . '&CISOPTR=' . $pointer . '&action=2&DMSCALE=100&DMWIDTH=' . 
        $image_info['width'] . '&DMHEIGHT=' . $image_info['height'] . 
        '"><img alt="" src="' . $thumbnail_url . $alias . '/id/' . $pointer .
        '" /></a>';
      break;
  default:
      $output .= '<a href="' . $getfile_url . $alias . 
      '/id/' . $pointer . '/filename/' . $filename . '"><img src="' . 
      $thumbnail_url . $alias . '/id/' . $pointer . '" /></a>';
  }
  
  $output .= '</div>';
  return $output;
}

/**
 * Render the current item's children.
 */
function render_items_children($alias, $pointer) {
  $self = Zend_Registry::get('self');
  $thumbnail_url = Zend_Registry::get('thumbnail_url');
  $skip_files_pattern = Zend_Registry::get('skip_files_pattern');
  $structure = get_compound_object_info($alias, $pointer);
  // i.e., Web services API says this item is not compound.
  $structure = simplexml_load_string($structure);
  if (is_object($structure)) {
	$output .= '<div class="lockssdm_compound_children_thumbnails">';
    $pages = $structure->xpath('//page');
    foreach ($pages as $page) {
      $page_pointer = (string) $page->pageptr;
      $page_file = (string) $page->pagefile;
      $page_title = (string) $page->pagetitle;
      // Only display the child if its file's extension is not in
      // $skip_files_pattern.
      if (!preg_match($skip_files_pattern, $page_file)) {
        $url = $self . 'display' . $alias . '/' . $page_pointer;
        $output .= '<div class="lockssdm_item_thumbnail"><a href="' . $url . '"><img alt="" src="' . 
        $thumbnail_url .  $alias . '/id/' . $page_pointer  . '" /></a></div>';
      }
    }
    $output .= '</div>';
  }
  return $output;
}

/**
 * Get the current item's parent item. Returns -1 if no parent found.
 */
function get_parent_item($alias, $pointer) {
  $ws_url = Zend_Registry::get('ws_url');
  $error_log = Zend_Registry::get('error_log');
  $request = $ws_url .'GetParent'. $alias . '/'. $pointer . '/json';
  $json = file_get_contents($request, false, NULL);
  $result = json_decode($json, TRUE);
  return $result['parent'];
}

/**
 * Get the image's dimensions, etc.
 */
function get_image_info($alias, $pointer) {
  $ws_url = Zend_Registry::get('ws_url');
  $request = $ws_url . 'dmGetImageInfo'. $alias . '/'. $pointer . '/xml';
  $xml = file_get_contents($request, false, NULL);
  $image_info_xml = new SimpleXMLElement($xml);
  $image_info = array('filename' => (string) $image_info_xml->filename,
    'type' => (string) $image_info_xml->type,
    'width' => (string) $image_info_xml->width,
    'height' => (string) $image_info_xml->height);
  return $image_info;
}

/**
 * Get the collection's field configration from the CONTENTdm API.
 */
function get_collection_field_info($alias) {
  $ws_url = Zend_Registry::get('ws_url');
  $request = $ws_url . 'dmGetCollectionFieldInfo'. $alias . '/json';
  $json = file_get_contents($request, false, NULL);
  return json_decode($json, TRUE);
}

/**
 * Get the Dublin Core fields' human-readable labels, to use in the
 * hidden, parsable item metadata.
 */
function get_dc_field_info() {
  $ws_url = Zend_Registry::get('ws_url');
  $request = $ws_url . 'dmGetDublinCoreFieldInfo/json';
  $json = file_get_contents($request, false, NULL);
  $raw_dc_field_info = json_decode($json, TRUE);

  // Convert from an anonymous array to a nick => name array.
  $dc_fields = array();
  foreach ($raw_dc_field_info as $field) {
    $dc_fields[$field['nick']] = $field['name'];
  }
  return $dc_fields;
}

/**
 * Replaces CONTENTdm field 'nicknames' with the corresponding human-readable
 * labels (i.e., field 'names'), for use in the displayed item metadata.
 */
function filter_metadata($alias, $item_metadata) {
  $admin_fields = Zend_Registry::get('admin_fields');
  $field_info = get_collection_field_info($alias);
  $item_metadata_with_lables = array();

  foreach ($item_metadata as $field_key => $field_value) {
    // Filter out the fields we don't want to display.
    if (in_array($field_key, $admin_fields)) {
      unset($field_key);
    }

    // Replace nicknames with labels from collection configuration.
    for ($i = 0; $i < count($field_info); $i++) {
      if ($field_key == $field_info[$i]['nick']) {
        $name = $field_info[$i]['name'];
        $item_metadata_with_lables[$name] = $field_value;
      }
    }
  }
  return $item_metadata_with_lables;
}

/**
 * Convert the item's CONTENTdm metadata into a structure that LOCKSS can
 * parse, using the fields' Dublin Core mappings.
 */
function generate_metadata_tags($alias, $item_info) {
  $admin_fields = Zend_Registry::get('admin_fields');
  $field_info = get_collection_field_info($alias);
  $dc_field_info = get_dc_field_info();

  $output = "\n" . '<ul id="dc_metadata">';
  foreach ($item_info as $field_key => $field_value) {
    // Filter out the fields we don't want to display.
    if (in_array($field_key, $admin_fields)) {
      unset($field_key);
    }
    // Filter out the fulltext field.
    if ($field_key == 'full') {
      unset($field_key);
    }
    // Replace nicknames with dc mappings from collection configuration.
    for ($i = 0; $i < count($field_info); $i++) {
      if ($field_key == $field_info[$i]['nick']) {
        $dc = $field_info[$i]['dc'];
        if (is_string($field_value)) {
          $dc_label = $dc_field_info[$dc];
          // $dc_label is blank if it wasn't mapped in get_dc_field_info(),
          // so skip it.
          if (strlen($dc_label)) {
            $output .= '<li><span class="name">' . $dc_label . 
              '</span>: <span class="content">' . $field_value . 
              '</span></li>' . "\n";
          }
        }
      }
    }
  }
  $output .= '</ul>';

  return $output;
}

?>
