LiteSpeed Cache for Drupal 8+
============================

Drupal 8+ is significantly changed from Drupal 7. They have converted from procedural programming to object-oriented programming. Drupal 8+ has a built-in page cache (for static content) and a dynamic page cache for logged in users. The latter basically works as a reverse proxy written in PHP. The built-in proxy is good if you have no other options, however, with the LiteSpeed Cache plugin you will see a great improvement in performance for your Drupal 9+ site.

See https://www.litespeedtech.com/products/cache-plugins for more information.



Prerequisites
-------------
This version of LiteSpeed Cache requires Drupal 9 or later and LiteSpeed Web Server (LSWS) 5.2.3 or later.



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

**Clear Cache**

* Clear this site

This option only clears the current Drupal installation. This helps if you have multiple Drupal installations on the same virtual host.

* Clear all

This button clears the entire LiteSpeed cache for this virtual host. This includes any other web apps using LSCache (WordPress, XenForo, etc.) on this vhost.

**Debug**

If turned on, LiteSpeed Cache will emit extra headers for testing while developing or deploying.

**Public Cache TTL**

Amount of time LiteSpeed web server will save pages in the public cache.


CLI commands
-------------

CLI commands are only allowed to execute from the website host server.

**Purge All Cache**

curl -I "http://example.com/lscpurgeall"
