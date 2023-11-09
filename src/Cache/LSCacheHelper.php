<?php

namespace Drupal\lite_speed_cache\Cache;

class LSCacheHelper
{
    public static function initHtaccess(){

        $htaccess = DRUPAL_ROOT . '/.htaccess';

        $directives = '### LITESPEED_CACHE_START - Do not remove this line' . PHP_EOL;
        $directives .= '<IfModule LiteSpeed>' . PHP_EOL;
        $directives .= '    CacheLookup on' . PHP_EOL;
        $directives .= '    php_value output_buffering Off' . PHP_EOL;
        $directives .= '</IfModule>' . PHP_EOL;
        $directives .= '### LITESPEED_CACHE_END';

        $pattern = '@### LITESPEED_CACHE_START - Do not remove this line.*?### LITESPEED_CACHE_END@s';

        if (file_exists($htaccess)) {
            $content = file_get_contents($htaccess);
            $newContent = preg_replace($pattern, $directives, $content, -1, $count);

            if ($count <= 0) {
                $newContent = preg_replace('@\<IfModule\ LiteSpeed\>.*?\<\/IfModule\>@s', '', $content);
                $newContent = preg_replace('@CacheLookup\ on@s', '', $newContent);
                file_put_contents($htaccess, $directives .PHP_EOL .$newContent);
            }
        } else {
            file_put_contents($htaccess, $directives);
        }
    }   

    public static function restoreHtaccess(){
        $htaccess = DRUPAL_ROOT . '/.htaccess';

        $pattern = '@### LITESPEED_CACHE_START - Do not remove this line.*?### LITESPEED_CACHE_END@s';
    
        if (file_exists($htaccess)) {
            $content = file_get_contents($htaccess);
            $newContent = preg_replace($pattern, '', $content, -1, $count);    

            if ($count <= 0) {
                $newContent = preg_replace('@\<IfModule\ LiteSpeed\>.*?\<\/IfModule\>@s', '', $content);
                $newContent = preg_replace('@CacheLookup\ on@s', '', $newContent);
                file_put_contents($htaccess, $newContent);
            } else {
                file_put_contents($htaccess, $newContent);
            }
        }
    
    }

    public static function initConfigs(){
        // Prevent gzip cause broken website layout
        $config = \Drupal::service('config.factory')->getEditable('system.performance');
        $config->set('css.preprocess', '0');
        $config->set('js.preprocess', '0');
        $config->set('css.gzip', '0');
        $config->set('js.gzip', '0');
        $config->save();  
    }

    public static function setLoginVary(){
        $lscInstance = new LSCacheCore();
        $lscInstance->checkVary("user:loggedin");
        $lscInstance->checkPrivateCookie();
    }

    public static function setLogoutVary(){
        $lscInstance = new LSCacheCore();
        $lscInstance->checkVary("");
    }

    public static function getSiteUrls($rootUrl){
        $database = \Drupal::database();
        $query = $database->select('path_alias', 'base_table');
        $query->condition('base_table.status', 1);
        $query->fields('base_table', ['alias','langcode']);
        $result = $query->execute()->fetchAllKeyed();

        $siteUrls = [$rootUrl];
        foreach($result as $alias => $langcode){
            $siteUrls[]= $rootUrl . $alias;
            $siteUrls[]= $rootUrl .'/' . $langcode. $alias;
        }
        return $siteUrls;
    }
  

    public static function curl($url) {
        $ch = curl_init();
    
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);       
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');
    
        $data = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);  
        curl_close($ch);
    
        if(!empty($data)) return $data;
        return $httpcode;
    
      }    
}