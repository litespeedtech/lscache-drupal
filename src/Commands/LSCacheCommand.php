<?php
namespace Drupal\lite_speed_cache\Commands;

use Drush\Commands\DrushCommands;
use Drupal\lite_speed_cache\Cache\LSCacheHelper;


/**
 * Drush command file.
 */
class LSCacheCommand extends DrushCommands {

  /**
   * Purge All LiteSpeed Caches
   * 
   * @command lscache:purgeall
   * @alias   lscache-purgeall
   * @param $rootURL Domain or root URL of website, eg: http://example.com
   */
  public function PurgeAllCommand($rootURL = '') {
    if(empty($rootURL)) {
      return $this->output()->writeln('need parameter @rootURL, eg: http://example.com');
    }

    $url = $rootURL .'/lscpurgeall';
    $result = $this->file_get_contents_curl($url);

    if($result){
      return $this->output()->writeln('Purge All Cache Successfull!');
    } else {
      return $this->output()->writeln('Purge All Cache Failed!');
    }

  }

  /**
   * Warmup whole site LiteSpeed Cache
   * 
   * @command lscache:warmup
   * @alias   lscache-warmup
   * @param $rootURL Domain or root URL of website, eg: http://example.com
   */
  public function WarmUpCommand($rootURL = '') {
    if(empty($rootURL)) {
      return $this->output()->writeln('need parameter @rootURL, eg: http://example.com');
    }

    $siteUrls = LSCacheHelper::getSiteUrls($rootURL);

    if(empty($siteUrls)){
      return $this->output()->writeln('No WarmUp Urls found');
    }

    $this->crawlUrls($siteUrls);

  }

  private function file_get_contents_curl($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FAILONERROR, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);       
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');

    $data = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);  
    curl_close($ch);

    if($httpcode!=200) {return false;}

    return true;
  }

  private function crawlUrls($urls) {
    set_time_limit(0);
    $acceptCode = array(200, 201);
    $total = count($urls);
    $current = 0;

    foreach ($urls as $url) {
        $current++;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'lscache_runner');
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        
        $buffer = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (in_array($httpcode, $acceptCode)) {
            $this->output->writeln($current . '/'. $total . ' Warm up:    ' . $url . "    success!");
        } else if($httpcode==428){
            $this->output->writeln("Web Server crawler feature not enabled, please check https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:enabling_the_crawler");
            break;
        } else {
            $this->output->writeln($current . '/'. $total . ' Warm up:    ' . $url . "    failed!");
        }
    }
}  

}