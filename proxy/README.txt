========================================
README.txt for the LOCKSSdm Proxy script
========================================

Overview
--------

The LOCKSSdm Proxy works with the LOCKSSdm CONTENTdm plugin to enable 
access to CONTENTdm items preserved in a LOCKSS network.

When a user requests a CONTENTdm item URL through the the LOCKSSdm Proxy, 
it polls your LOCKSS box to see if the CONTENTdm web server responds. If the
server does respond, the user is redirected seamlessly to the CONTENTdm URL.
If it doesn't, the LOCKSSdm Proxy returns the copy of the content that is 
supplied by the LOCKSS box. 

In addition, the LOCKSSdm Proxy rewrites URLs in the preserved content's
HTML such that once a user gains access to a resource via the Proxy, all 
links to other items in CONTENTdm (such as parent items or children items)
are prepended with the Proxy's URL. This rewriting ensures that your LOCKSS
box is the source for all the parts of a CONTENTdm item. Only URLs pointing
to the CONTENTdm host are rewritten in this way -- links to external sites are
not rewritten. Therefore, the Simple LOCKSS Gateway cannot act as an open 
proxy to arbitrary URLs.

The Proxy also cleans up the HTML that LOCKSS presents when it encounters an
error. The proxy also allows local administrators to add custom HTML markup and
CSS files to the proxied content. This feature enables libraries to preserve some
of the local branding, such as their library’s standard header or navigation links,
that is stripped out of the simplified copies of items preserved in the PLN,
and to customize the message that explains why the user is seeing a simplified
version of the CONTENTdm item.


Installing and configuring the LOCKSSdm Proxy
---------------------------------------------

Place the contents of 'proxy' directory in the LOCKSSdm CONTENTdm plugin
zip file on a server running PHP 5.x. This server should not be your
CONTENTdm server, since it needs to be accessible if your CONTENTdm server
is down. 

You will need to enable the PHP cURL extension for the gateway to work
(most PHP installs have this enabled by default). The Proxy comes with
the PHP Simple HTML DOM Parser library (http://simplehtmldom.sourceforge.net/),
distributed under the terms of the MIT License.

The IP address of the server you install Proxy on must be registered with 
your LOCKSS box. To do this, log into your box's admin interface, go to 
Content Access Control, add the server's IP address to the "Allow Access"
list, and then click on the Update button.

For the Proxy to work, you will need to define the following variables
in config.php:

1) The URL and proxy port number of your LOCKSS box. The port is the one 
  indicated in your LOCKSS box's admin interface, under Content Access 
  Options/Content Server Options, in the "Enable content proxy" setting.

  $lockss_box = 'lockssbox.yourlib.net:8080'; 

2) Your CONTENTdm server's hostname. If you run the public interface to
  CONTENTdm on a port other than 80, include it.

  $contentdm_host = 'yourcontentdmserver.yourlib.net';
  
3) The path to your LOCKSSdm plugin directory on your CONTENTdm server.
  You define the directory where your LOCKSSdm plugin is stored, following
  instructions in the README.txt in the 'contentdm' directory in the LOCKSSdm
  CONTENTdm plugin zip file.
  
  $path_to_lockssdm_plugin = 'http://'. $contentdm_host . '/cdm/lockssdm/';

4) The URL of the lockssdm.php script on your server. Be sure to include 
  the '?url=' at the end of the URL, as illustrated below.

  $this_script = 'http://someserver.yourlib.net/path/to/lockssdm.php?url='; 

5) HTML markup you want to show at the top of the proxied HTML (item displays,
  collection mannifests, and list of collections). If you want to include an
  external file, use $local_header_string = file_get_contents(); Leave blank
  if you do not want to include a local header.

  $local_header = '<div id="local_header"><a href="http://www.yourlib.net">'
    . '<img src="http://www.yourlib.net/images/banner_left.jpg"></a>';

6) HTML markup you want to show just below the markup defined in $local_header
  explaining to the user whay he or she is seing a simplified version of your
  CONTENTdm item. If you want to include an external file, use 
  $local_header_string = file_get_contents(); Leave blank if you do not
  want to include a note to the end user.

  $lockss_disclaimer = '<span>You are viewing a simplified, backup-friendly
  version of our CONTENTdm <a href="' . $this_script . 'http://' . $contentdm_host
    . '">collections</a>.</span>';

7) The absolute URL to a CSS file you want to inject into the proxied HTML
  (item displays, collection mannifests, and list of collections). This CSS
  overrided declarations in lockssdm.css, other cascading rules being equal.
  Leave blank if you do not want to add any local CSS to the proxied HTML.

  $local_css_url = 'http://www.yourlib.net/library.css';


Routing users through the Proxy
-------------------------------

To use the LOCKSSdm Proxy, append the CONTENTdm 'reference URL' of an item
preserved in your LOCKSS box to the end of the URL for the proxy script
(much like you would do if using Ezproxy or its competitors to provide off-
campus access to ejournals or databases):

http://your.library.net/path/to/lockssdm.php?url=http://contentdm.your.library.net/cdm/ref/collection/foobar/id/2716

You can also proxy links to specific collections:

http://your.library.net/path/to/lockssdm.php?url=http://contentdm.your.library.net/cdm/landingpage/collection/foobar

This URL will lead the user to the LOCKSS Manifest for the specified
collection, which functions like a rudimentary browse list.

To show the user a list of all preserved collections from your CONTENTdm server,
proxy the link of your server's base URL:

http://your.library.net/path/to/lockssdm.php?url=http://contentdm.your.library.net

These composite URL should be given to end users, or more conveniently, 
should be used on your library's website instead of the direct URL to the 
resource. As long as users access a resource using the proxied version of 
CONTENTdm reference URL, your LOCKSS box will return preserved content to 
them in the event that your CONTENTdm server goes down. When your CONTENTdm
server is working normally, users won't notice anything other than the longer
URLs.

Note that for compound items, CONTENTdm provides several reference URLs.
Only reference URLs to 'entire objects', not reference URLs for specific
pages with compound items, will work.


License
-------

Copyright 2013 Mark Jordan (mjordan@sfu.ca).

This work is licensed under the MIT License; see MIT-LICENSE.txt for details.


