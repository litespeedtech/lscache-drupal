LiteSpeed Cache for Drupal 8
============================

After Drupal 7 a lot has changed in Drupal 8. They have converted from procedural programming to Object-Oriented Programming. Drupal 8 has built-in Page Cache (for static Content) and Dynamic Page Cache for (for logged in user). Which basically work as reverse proxy written in PHP. Built in the proxy is good in case you have no other options, however, with LiteSpeed Cache plugin there is a great improvement in performance for your Drupal 8 Site.

See https://www.litespeedtech.com/products/cache-plugins for more information.



Prerequisites
-------------
This version of LiteSpeedCache requires Drupal 8.xx or later and LiteSpeed Web Server (LSWS) 5.2.3 or later. 



Download
-------------
Download LiteSpeed Cache Module to your local computer from:

    https://github.com/litespeedtech/lscache_drupal/archive/master.zip

Enable LiteSpeedCache using rewrite rules
-------------

    <IfModule LiteSpeed>
	CacheLookup on
    </IfModule>

Install Plugin
-------------
Once you have downloaded the plugin, you can install by visiting: 

    http://example.com/admin/modules/install

Using the browse button you can upload the plugin you just downloaded and start the installation. Once installed enable plugin by going to:

http://example.com/drupal/admin/modules


![LiteSpeed Cache Drupal](https://www.litespeedtech.com/support/wiki/lib/exe/fetch.php/litespeed_wiki:cache:enable_lscache_drupal.png)

* Use the search box to search for a module.
* Check the plugin checkbox.
* Click Install.


Configurations
-------------

This step is optional, once the plugin is activated your cache is already up and running, but on configuration screen, you can customize few settings.

Go to → http://example.com/drupal/admin/config/development/lscache

![LiteSpeed Cache Drupal Plugin Configurations](https://www.litespeedtech.com/support/wiki/lib/exe/fetch.php/litespeed_wiki:cache:enable_lscache_drupal.png)

**Clear Cache**

* Clear this site

This option only clears the current Drupal installation, this helps if you have multiple Drupal installations on a same virtual host.

* Clear all

This can help you clear complete cache for this virtual host.

**Debug**

If turned on LiteSpeed cache plugin will emit extra headers for testing while developing or deploying.

**ESI**

Turn this off when you are on OpenLiteSpeed, but even if it is turned on it will not create any issues.

**Max Age**

Amount of time LiteSpeed web server will save pages in the public cache.

**Max Age Private**

Amount of time LiteSpeed web server will save pages in the private cache.

