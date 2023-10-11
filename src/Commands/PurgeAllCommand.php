<?php
namespace Drupal\lite_speed_cache\Commands;

use Drush\Commands\DrushCommands;


/**
 * Drush command file.
 */
class PurgeAllCommand extends DrushCommands {

  /**
   * Purge All LiteSpeed Caches
   * 
   * @command lscache:purgeall
   * @alias   lscache-purgeall
   * @param $rootURL Domain or root URL of website, eg: http://example.com
   */
  public function PurgeAllCommand($rootURL = '') {
    if(empty($rootURL)) {
      return $this->output()->writeln('rootURL is empty!');
    }

    $url = $rootURL .'/lscpurgeall';
    $result = $this->file_get_contents_curl($url);

    if($result){
      return $this->output()->writeln('Purge All Cache Successfull!');
    } else {
      return $this->output()->writeln('Purge All Cache Failed!');
    }

  }


  protected function file_get_contents_curl($url) {
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

}