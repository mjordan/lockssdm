============================================
README.txt for the LOCKSSdm CONTENTdm Plugin
============================================

Overview
--------

The LOCKSSdm plugin for CONTENTdm 6.1 and higher enables libraries to 
1) preserve items in CONTENTdm in a LOCKSS network (typically a Private 
LOCKSS Network) and 2) provide on-demand access to those preserved items
in the event that their CONTENTdm server becomes unavailable using an
accompanying proxy script. The plugin and the proxy are two distinct sets
of PHP scripts. Use of the proxy is described in the proxy/README.txt file;
use of the CONTENTdm plugin is described in this file.

The LOCKSSdm plugin creates a simplified version of all items in each
CONTENTdm collection that is configured to be harvested by the LOCKSS
crawler. These simplified versions are the ones that are preserved in
the LOCKSS network. They include all the metadata, full text, thumbnails, 
and media files (e.g., images, video, PDFs, etc) that make up the items,
but they do not include the embedded viewers, compound item navigation,
and other interface components that would be problematic for LOCKSS to
harvest and preserve.

Access to the preserved versions of the CONTENTdm items is enabled through
the LOCKSSdm Proxy script, which acts like Ezproxy and its competitors by
creating special URLs to items in CONTENTdm in the form 

  http://url-to-proxy?url=http://contentdm-reference-url-to-item

As long as users access a resource using the proxied version of CONTENTdm 
reference URL, your LOCKSS box will return preserved content to them in 
the event that your CONTENTdm server goes down. When your CONTENTdm server
is working normally, users won't notice anything other than the longer URLs.
Note that only reference URLs to 'entire objects', not reference URLs for 
specific pages with compound objects, will work, although LOCKSS preserves
and provides access to all child items within compound objects.


Installing the LOCKSSdm plugin
------------------------------

Consult with the administrators of your Private LOCKSS Network before
installing this plugin, as your network will need to have enough disk
space to store the collections you intend to preserve.

1) As the CONTENtdm admin user, log into your serve's website config tool
  at http://contentdm.yourlib.net/config/

2) On the 'Global' tab, click on 'Custom Pages/Scripts', and then 'Custom Pages.'

3) Click on 'Add Custom Page.'

4) In the 'Name' field, enter 'lockssdm'. Keep 'Show this page on my website'
  checked but uncheck 'Use website layout and styles'. This 'subdirectory'
  will appear in the URLs created by the plugin.

5) Click on 'save changes.'

6) Upload the following files from the 'contentdm' directory in the LOCKSSdm
plugin zip file:

  index.php
  aus.php
  common.php
  config_zend.php
  
  You will need to modify config.php (described below), so do not upload
  it yet. Also, do not upload lockssdm.css into the plugin directory;
  you will need to upload it using CONTENTdm's 'Custom CSS' tool, described
  in step 7, below.
  
  To upload the files, click on 'manage files'. In the 'name' column in
  the file management dialog box, click on the directory name you created
  in step 4, above. Then click on the 'Upload' tab. One file at a time,
  choose the files listed above and upload them to the plugin directory
  you have created.

You are now ready to upload the plugin files. Before you do so, you need
to change some configuration settings in the 'config.php' file, described
below. Make sure you upload config.php after you edit it.

7) Upload lockssdm.css using CONTENTdm's 'Custom CSS' tool, which is linked
  in the left-hand menu within the web admin tool, in the 'Custom Pages /
  Scripts' section. To upload the file, click on 'Custom CSS', then 'browse'.
  Select 'lockssdm.css' and click on 'Upload'.
  

Static and dynamic manifests modes
----------------------------------

The most important decision you need to make is whether you will be running
your LOCKSSdm plugin in 'static manifests' mode or in 'dynamic manifests'
mode. In static mode, you need to run a script to generate the LOCKSS
Archival Unit (AU) manifest files that you then upload to the CONTENTdm
server; in dynamic mode, the AU manifest files are generated on the fly
during HTTP requests to the AU URLs by the LOCKSS harvester.

Static mode is much more reliable in that the LOCKSS harvester will not
time out when retrieving the manifests. The only disadvantage to static
mode is that you will need to periodically regenerate your manifest files
and upload them to the CONTENTdm server using the standard web admin
interface provided by CONTENTdm. Static mode is the preferred configuration
option and you should use it unless uploading the manifest files poses a
problem for you.

Dynamic mode eliminates the need to generate manifest files and upload them,
but since they are generated in realtime during harvest, and since they can
require quite a bit of time to generate, there is a very good chance that
CONTENTdm's web server, or PHP, will time out and cause the LOCKSS harvester
to fail. You should only use the dynamic mode if you do not have easy access
to the CONTENTdm web admin tool.


Configuring the plugin
----------------------

Once you have chosen static or dynamic mode (static being the recommended
option), you are ready to configure the plugin and upload the files to
CONTENTdm.

1) Static or dynamic manifests mode. Set to FALSE if you are using dynamic
  manifests.

  $static_manifests = TRUE;
  
2) Your CONTENTdm server's hostname, including the 'http://' but not 
  the trailing slash.

  $server = 'http://contentdmserver.yourlibrary.net';

2) Your CONTENTdm website's hostname, including the 'http://' but not
  the trailing slash. This may be the same as $server.

  $website = 'http://contentdmwebsite.yourlibrary.net';
  
3) The subdirectory you created in the CONTENTdm 'Custom pages' tool, following
  the instructions above (step 4). Do not include either the leading or trailing
  slash.

  $lockssdm_directory = 'lockssdm';

4) The path on the CONTENTdm server's file system where your plugin files
  will be uploaded to. To determine this path, go back to the file upload
  dialog box you used in step 6, above. The path to your plugin files can
  be found at the top of the "Upload a file..." dialog box. Be sure to 
  include the trailing slash. You only need to set this variable if 
  $static_manifests is set to TRUE. A typical value is provided here as
  an example:
  
  $manifest_files_dir = '/usr/local/Content6/Website/public_html/ui/custom/default/collection/default/resources/custompages/lockssdm/';
  
5) The list of CONTENTdm collections you want to preserve in your LOCKSS
  network. This list is a PHP associative array, and uses collection 'aliases'
  (the unique abbreviation for each collection) and collection names stored
  with the aliases as array keys (on the left) and names as values (on the
  right). Below is an example of how this array is structured:
  
  $collections_to_harvest = array(
    '/foo' => 'Collection Foo',
    '/bar' => 'Collection Bar',
    '/fob' => 'Collection Fob',
    '/baz' => 'Collection Baz',
  );
  
Once you have changed the configuration values, upload config.txt to your
plugin directory.


Generating the manifest files
-----------------------------

If you are running the plugin in 'static manifests' mode, you will need to
generate the manifest files and upload them to your plugin directory. To
generate the manifests, you need to run the 'aus.php' script at the command
prompt on a computer with PHP installed. You can do this on a Windows, Mac,
or Linux workstation, or upload all of the plugin files to a server running
PHP and run the aus.php script there; if you upload the files to a server
to generate the manifests, you will need to download the manifests to your
workstation in order to upload them to CONTENTdm (i.e., you can't ftp or scp
the manifests from a server to CONTENTdm, you must use the web-based admin
tool to upload them).

To generate the manifest files, make sure the options in config.php are as
you want them, and then, at a command line prompt, run the following command:

  php aus.php
  
After a few seconds, you will see messages confirming that the manifests
are being generated. When the script stops running, upload all of the new
.html files produced by the script to your LOCKSSdm plugin directory using
same steps you used to upload index.php, aus.php, and the othe files, as
described in step 6 in the section 'Installing the LOCKSSdm plugin' above.
Also, make sure you click on the 'publish' button after uploading the files.


Testing the plugin
------------------

To test the plugin, make sure you have uploaded the static manifest files
if you are using that option. If you are using the dynamic manifests option,
you do not need to upload any additional files.

Point your web browser at the following URL on your CONTENTdm server:

  http://contentdm.yourlib.net/cdm/lockssdm/aus

where 'contentdm.yourlib.net' is your server's hostname and 'lockssdm'
is the name of your plugin directory (i.e., the same value that you used
in the $lockssdm_directory variable in config.php). You should see a list
of Archival units corresponding to the list of collections you identified
in the $collections_to_harvest configuration variable. The list may contain
more than one Archival Unit link per collection, depending on the size of
the collections.

If you click on each link, you will see that Archival Unit's manifest page.
If you then click on an item-level link on the manifest page, you will see
the simplified version of the CONTENTdm item that LOCKSS will preserve,
and that will be presented to your users in the event that LOCKSS is asked
to provide the preserved version, using the LOCKSSdm Proxy.


Testing the LOCKSS versions of the preserved content
----------------------------------------------------

After the items in the configured collections have been harvested by your
LOCKSS box, you should be able to log into your box and view the preserved
content. To do so, log into your LOCKSS box using the admin credentials
and go to your 'content server' URL, which should look like:

  http://yourlockssbox.yourlib.net:8083/ServeContent
  
The hostname should be the same one you used in the $lockss_box configuration
variable in lockssdm.php proxy script, but the port will be 8083 (or whatever
is configured for your content server under Content Access Options/Content 
Server Options in your LOCKSS box admin interface).

To view the preserved content, point your browser at this URL, find the
CONTENTdm Archival Units, and click through until you see an item.

Alternatively, you can use your LOCKSS box to search for a specific preserved
item. In your box's admin interface, go to Debug Panel, then enter one of the
URLs from the manifests linked from http://contentdm.yourlib.net/cdm/lockssdm/aus
in the "Find Preserved URL" field and hit that button. Click on the value in
the 'Size' column to see the preserved version of the CONTENTdm item. This
method of testing only works with the URLs generated in the manifests; it does
not work with normal CONTENTdm reference URLs.


License
-------

Copyright 2013 Mark Jordan (mjordan@sfu.ca).

This work is licensed under the MIT License; see MIT-LICENSE.txt for details.
 
