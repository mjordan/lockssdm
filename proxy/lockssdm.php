<?php

/**
 * @file
 * LOCKSSdm Proxy, a script that acts as a proxy between CONTENTdm end 
 * users and a LOCKSS box. The CONTENTdm server must have the LOCKSSdm 
 * plugin installed and configured.
 * 
 * Requires PHP's cURL extension and the PHP Simple HTML DOM Parser library 
 * (http://simplehtmldom.sourceforge.net/), which is distributed with this
 * script.. 
 *
 * Configuration variables are defined in 'config.php'.
 *
 * Copyright 2013 Mark Jordan (mjordan@sfu.ca).
 *
 * Licensed under the MIT License; see MIT-LICENSE.txt for details.
*/


// Get the configuration variables.
require_once('config.php');

/**
 * You should not have to change anything below this line.
 */
 
// File to log stuff to, if you invoke log_var() while troubleshooting, etc. 
$log_var_file = '/tmp/lockssdm_log.txt';

// List of element => attribute pairs we want this proxy script to rewrite. 
$rewrite_elements = array(
  'a' => 'href',
  'img' => 'src',
  'link' => 'href',
);

/**
 * Main script logic.
 */

// Grab the destination CONTENTdm URL. We can't use $_GET['url'] here since URLS 
// to images in CONTENTdm contain &foo= parameters and we want everything
// after ?url=.
$query_string = $_SERVER["QUERY_STRING"];
$destination_url = preg_replace('/^url=/', '', $query_string);
if (empty($destination_url)) {
  echo '<html><body>Error: URL parameter is required</body></html>';
  exit;
}

// Check the $destination_url to make sure the host is allowed 
// to be proxied by this script.
$url_parts = parse_url($destination_url);
if ($url_parts['host'] != $contentdm_host) {
  echo '<html><body>Error: Unregistered URL</body></html>';
  exit;
}

// Check to see if the destination server responds with a '200' HTTP 
// code. If it does, redirect the user and exit.
if (check_contentdm_reference_url($destination_url)) {
  $header_string = 'Location: ' . $destination_url;
  header($header_string, TRUE, 303);
  exit;
}

// If we made it this far (i.e., we didn't get a 200 response back 
// from the CONTENTdm server), we ask LOCKSS to step in and return
// its cached version of the content at $destination_url.

// Include the PHP Simple HTML DOM Parser library.
require_once('simplehtmldom/simple_html_dom.php');

// Convert the incoming CONTENTdm reference URL into its equivalent in the
// LOCKSSdm CONTENTdm plugin version. However, we only want to convert the
// item reference URLs, we need to leave all URLs to thumbnails or object
// files intact as they are what is harvested by LOCKSS.
if (preg_match('/cdm\/ref\/collection\//', $destination_url)) {
  $reference_url_parts = parse_contentdm_reference_url($destination_url);
  $destination_url = $path_to_lockssdm_plugin . 'display' . 
    $reference_url_parts['alias'] . '/' . $reference_url_parts['pointer'];
}

// Convert incoming collecion URLs like http://content.lib.sfu.ca/cdm/landingpage/collection/vpl
// into LOCKSSdm collection URLs like http://content.lib.sfu.ca/cdm/lockss/manifest/vpl/1.
if (preg_match('/cdm\/landingpage\/collection\//', $destination_url)) {
  $alias = parse_contentdm_collection_url($destination_url);
  // All collections have at least one manifest page, so point users to that one.
  $destination_url = $path_to_lockssdm_plugin . 'manifest' . $alias . '/1';
}

// Retrieve LOCKSS's copy of the content via cURL.
$ch = curl_init($destination_url);
// LOCKSS box is acting as a proxy.
curl_setopt($ch, CURLOPT_PROXY, $lockss_box);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
$lockss_content = curl_exec($ch);

// Get the HTTP response from the LOCKSS box.
$info = curl_getinfo($ch);
// Grab the HTTP content type header so we can pass it back to the client.
$content_type = $info['content_type'];

// We don't want to rewrite non-HTML content like images, audio, or CSS. 
// If the content retrieved from the LOCKSS proxy is not HTML, send it 
// back to the client as is, along with the corresponding content-type
// header.
if (!preg_match('/^text\/html/', $content_type)) {
  header('Content-type: ' . $content_type);
  echo $lockss_content;
  // Using flush() makes the download of large (e.g. audio) files more reliable.
  flush();
  exit;
}

// If LOCKSS doesn't have a copy of a resource at the requested URL, it returns 
// a custom 502 page that lists what it thinks are likely AU URLs. First, we
// rewrite all URLs in it and then we clean up the HTML before sending it back
// to the user.
$proxied_html = str_get_html($lockss_content);
if ($info['http_code'] == '502') {
  foreach ($proxied_html->find('a') as $a) {
    $href_host = parse_url($a->href, PHP_URL_HOST);
    if ($href_host == $contentdm_host) {
      $a->href = $this_script . $a->href;
    }
  }
  // Return the custom 502 page with the rewritten URLs to the client. 
  $lockss_502_html = $proxied_html->save();
  echo modify_502_page($lockss_502_html);
  exit;
} else {
  // If LOCKSS didn't return a 502, rewrite the HTML and return it to the client. 
  // First, replace some out-of-context CONTENTdm markup with our own. We inlcude
  // a link back to the CONTENTdm host, which will trigger a 502 from LOCKSS (since
  // it never harvested the CONTENTdm server's home page). The resulting 502 page
  // serves as a simple collection browse interface.
  if (strlen($local_header)) {
    $proxied_html->find('div[id=breadcrumb_top_content]', 0)->innertext = $local_header;
  }
  if (strlen($lockss_disclaimer)) {
    $proxied_html->find('div[id=breadcrumb_top_content]', 0)->outertext = $proxied_html->find('div[id=breadcrumb_top_content]', 0)->outertext
      . '<div class="lockssdm_disclaimer" style="font-family: sans-serif">' . $lockss_disclaimer . '</div>';
  }

  // Since CONTENTdm uses relative URLs in its <link> elements for CSS stylesheets,
  // we need to prepend the CONTENTdm server's hostname to the HREF for the lockssdm.css
  // stylesheet (the only one used in the LOCKSS version of the content).
  foreach ($proxied_html->find('link') as $link) {
    if (preg_match('/lockssdm\.css$/', $link->href)) {
      $link->href = $this_script . 'http://' . $contentdm_host . $link->href;
      // Inject CSS idendtified in config option $local_css_url.
      if (strlen($local_css_url)) {
        $link->outertext = $link->outertext . '<link type="text/css" href="' . $local_css_url . '" rel="stylesheet" />';
      }
    }
  }
  // Finally, find all the element => attribute combinations we want to rewrite 
  // and prepend the $this_script 
  // URL if the URL's host name is $contentdm_host.
  foreach ($rewrite_elements as $element => $attribute) {
    foreach($proxied_html->find($element) as $e) {
      $host = parse_url($e->$attribute, PHP_URL_HOST);
      if ($host == $contentdm_host) {
        $e->$attribute = $this_script . $e->$attribute;
       }
    }
  }

  // Send the rewritten HTML to the client.
  header('Content-type: ' . $content_type);
  echo $proxied_html;
  exit;
}

/**
 * Functions.
 */

/**
 * Given a CONTENTdm reference URL, returns TRUE if the HTTP status code 
 * supplied by the web server is '200'. Returns FALSE if file_get_contents()
 * fails or if the response code is not '200'.
 */
function check_contentdm_reference_url($url) {
  $ch = curl_init(); 
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD'); 
  curl_setopt($ch, CURLOPT_HEADER, 1); 
  curl_setopt($ch, CURLOPT_NOBODY, TRUE); 
  curl_setopt($ch, CURLOPT_URL, $url); 
  $res = curl_exec($ch);
  $info = curl_getinfo($ch);
  if ($info['http_code'] == '200') {
    return TRUE;
  }
  else {
    return FALSE;
  }
}

/**
 * Given a CONTENTdm reference URL, returns an associative array containing 
 * the alias (with leading /) and pointer.
 * URLs for simple or compound items look like: http://content.lib.sfu.ca/cdm/ref/collection/ubcCtn/id/10201
 * URLs for children items look like: http://content.lib.sfu.ca/cdm/ref/collection/ubcCtn/id/10201/show/10171
 */
function parse_contentdm_reference_url($url) {
  $parts = parse_url($url);
  $path = preg_replace('/cdm\/ref\/collection\//', '', $parts['path']);
  $alias = preg_replace('/\/id.+$/', '', $path);
  $pointer = preg_replace('/^.+\//', '', $path);
  return array('alias' => $alias, 'pointer' => $pointer);
}

/**
 * Given a CONTENTdm collection URL, returns the alias (with leading /).
 * Collection URLs look like http://content.lib.sfu.ca/cdm/landingpage/collection/vpl.
 */
function parse_contentdm_collection_url($url) {
  $alias = preg_replace('/^.*cdm\/landingpage\/collection/', '', $url);
  return $alias;
}

/**
 * Clean up the 502 page produced by LOCKSS.
 */
function modify_502_page($lockss_502_html) {
  global $this_script;
  global $contentdm_host;
  global $local_header;
  global $lockss_disclaimer; 
  global $local_css_url;

  $html = str_get_html($lockss_502_html);
  $html->find('title', 0)->innertext = 'Browse our collections';
  $html->find('h2', 0)->innertext = '';
  $html->find('font[size=+1]', 0)->innertext = '';
  // We add this div so we can apply the same CSS to the 502 page as we do to other proxied pages.
  $html->find('font[size=+1]', 0)->outertext = $html->find('font[size=+1]', 0)->outertext . '<div id="breadcrumb_top_content">' . $local_header . '</div>';
  $html->find('font[size=+1]', 0)->outertext = $html->find('font[size=+1]', 0)->outertext . '<div class="lockssdm_disclaimer">' . $lockss_disclaimer . '</div>';
  $html->find('table', 0)->outertext = '<div class="lockssdm_content_wrapper"><div style="margin-top: -1em; margin-bottom: 1em">Browse our collections</div>' . $html->find('table', 0)->outertext . '</div>';
  // Add the lockss.css link.
  $html->find('title', 0)->outertext = $html->find('title', 0)->outertext . '<link type="text/css" href="' . $this_script . 'http://' . $contentdm_host . '/ui/custom/default/collection/default/css/custom/lockssdm.css" rel="stylesheet" />';
      // Inject CSS identified in config option $local_css_url.
      if (strlen($local_css_url)) {
        $html->find('title', 0)->outertext = $html->find('title', 0)->outertext . '<link type="text/css" href="' . $local_css_url . '" rel="stylesheet" />';
      }
  $output = $html->save();
  $output = preg_replace('/<br><br>.+Units:/', '', $output);
  return $output;
}

/**
 * Log any variable to a file. Adapted from the Drupal devel module's 
 * dd() function.
 */
function log_var($data, $label = NULL) {
  global $log_var_file;
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

?>
