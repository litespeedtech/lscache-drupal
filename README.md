LiteSpeed Cache for Drupal 8+
============================

* Fastest Page cache for Drupal CMS.

* Page Cache for both Logged In and Logged Out users. 

* Auto Purge relate Page Caches when content changes.

* Drush and non-Drush cli commands for Cache warmup/clear.

* Web GUI warmup will also warmup "Private Cache for Logged In Users" if enabled.

* Support latest release of Drupal 8+, 9+ and 10+ .

See https://www.litespeedtech.com/products/cache-plugins for more information.



Prerequisites
-------------
This version of LiteSpeed Cache requires Drupal 8 or later and LiteSpeed Web Server (LSWS) 5.2.3 or later.


Download
-------------
Download LiteSpeed Cache Module to your local computer from:

    https://github.com/litespeedtech/lscache-drupal/archive/master.zip

If you use Composer to manage dependencies, require the module in your project `composer.json`:

```
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:litespeedtech/lscache-drupal.git"
    }
  ],
  "require": {
    "litespeedtech/lscache-drupal": "dev-master"
  },
```

Install Plugin
-------------
Once you have downloaded the plugin, you can install by visiting:

    http://example.com/admin/modules/install

Using the browse button you can upload the plugin you just downloaded and start the installation. Once installed, enable the plugin by going to:

http://example.com/drupal/admin/modules


![LiteSpeed Cache Drupal](https://www.litespeedtech.com/support/wiki/lib/exe/fetch.php/litespeed_wiki:cache:enable_lscache_drupal.png)

* Use the search box to search for a module.

* Check the checkbox next to LiteSpeed Cache.

* Click Install.

* Turn on LiteSpeed Cache in Module Settings.


Configurations
-------------

This step is optional. Once the plugin is activated, your cache is already up and running, but on the configuration screen, you can customize few settings.

Go to

    http://example.com/admin/config/development/lscache
    
  ![LiteSpeed Cache Drupal Plugin Configurations](https://www.litespeedtech.com/support/wiki/lib/exe/fetch.php/litespeed_wiki:cache:configure-lscache.png?cache=)

**Warmup this site**

Warmup the LSCache of current Drupal site. It will also warmup "Private Cache for Logged In Users" if enabled.

**Clear this site**

Clears the LSCache of current Drupal site. It will not clear LSCache of other sites if multiple CMS sites run on the same virtual host.

**Public Cache TTL**

Amount of time LiteSpeed web server will save pages in the public cache.

**Private Cache TTL**

Amount of time LiteSpeed web server will save "ESI Block contents" in the Private cache. OpenLiteSpeed does not support Private Cache.

**ESI Blocks Setting**

The ESI block list of general logged in pages. ESI Block should be the DIV ID inside HTML source. for example:

a DIV block: <div id="bar-administrator">...<div>

ESI Block Setting: id=bar-administrator

**Debug**

If turned on, LiteSpeed Cache will print lscache header to LSWS web server Log files.


CLI commands
-------------

CLI commands are only allowed to execute from the website host server.

**Purge All Cache**

```
curl -N "http://example.com/lscpurgeall"
```

or in /drupal_root/vendor/bin folder execute drush command:

```
./drush lscache:purgeall example.com
```

**WarmUp whole website**

```
curl -N "http://example.com/lscwarmup"
```

or in /drupal_root/vendor/bin folder execute drush command:

```
./drush lscache:warmup example.com
```

CLI warm up command can only warmup public page caches, if you wants to warmup "Private Cache for Logged In users", you need to use the Web Gui warmup in LSCache admin panel.
