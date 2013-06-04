<?php

/**
 * @file
 * Configuration file for the LOCKSSdm proxy script.
 *
 * Copyright 2013 Mark Jordan (mjordan@sfu.ca).
 *
 * Licensed under the MIT License; see MIT-LICENSE.txt for details.
*/

/**
 * You will need to change $lockss_box, $contentdm_host, and $this_script as
 * described in README.txt. You may also need to change $path_to_lockssdm_plugin
 * if you installed the LOCKSSdm CONTENTdm plugin in a directory other
 * than the one recommended in its README.txt file.
 */

// Your LOCKSS box's hostname and Content Proxy port. Do not include
// 'http://',
$lockss_box = 'yourlockssbox.yourlib.net:8080';

// Your CONTENTdm server's hostname. Do not include 'http://'. If you run the 
// public interface to CONTENTdm on a port other than 80, include it.
$contentdm_host = 'contentdm.yourlib.net';

// The path to your LOCKSSdm plugin.
$path_to_lockssdm_plugin = 'http://'. $contentdm_host . '/cdm/lockss/';

// The absolute URL of this script, which is prepended to proxied URLs below.
// Include '?url=' at the end.
$this_script = 'http://proxy-host.yourlib.net/lockssdm.php?url=';

// HTML markup you want to show at the top of the proxied HTML (item displays,
// collection mannifests, and list of collections). If you want to include an
// external file, use $local_header_string = file_get_contents(); Leave blank
// if you do not want to include a local header.
$local_header = '<div id="local_header"><a href="http://www.yourlib.net">' 
  . '<img src="http://www.yourlib.net/images/banner_left.jpg"></a>';

// HTML markup you want to show just below the markup defined in $local_header.
// explaining to the user whay he or she is seing a simplified version of your
// CONTENTdm item. If you want to include an external file, use 
// $local_header_string = file_get_contents(); Leave blank if you do not
// want to include a note to the end user.
$lockss_disclaimer = '<span>You are viewing a simplified, backup-friendly version 
  of our CONTENTdm <a href="' . $this_script . 'http://' . $contentdm_host 
  . '">collections</a>.</span>';

// The absolute URL to a CSS file you want to inject into the proxied HTML
// (item displays, collection mannifests, and list of collections). This CSS overrided
// declarations in lockssdm.css, other cascading rules being equal. Leave blank
// if you do not want to add any local CSS to the proxied HTML.
$local_css_url = 'http://www.yourlib.net/library.css';

