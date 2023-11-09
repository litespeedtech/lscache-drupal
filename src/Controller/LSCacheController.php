<?php

namespace Drupal\lite_speed_cache\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\lite_speed_cache\Cache\LSCacheCore;
use Drupal\lite_speed_cache\Cache\LSCacheHelper;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Session\SessionConfiguration;

class LSCacheController extends ControllerBase {

    public function purgeAll() {

        $visitorIP =  $_SERVER['REMOTE_ADDR'];
        $serverIP = $_SERVER['SERVER_ADDR'];
        
        if(($visitorIP=="127.0.0.1") || ($serverIP=="127.0.0.1") || ($visitorIP==$serverIP)){

            LSCacheCore::getInstance()->purgeAllPublic();
            $result = 'All LiteSpeed Cache purged!' . PHP_EOL;

        } else {
            $result = '<h3>Access denied! <br> please access from localhost with "curl " command!</h3>' . PHP_EOL;
        }

        return new HtmlResponse(['#markup'=>$result]);
        
    }

    public function warmup(Request $request) {
        $rootURL =  $request->getSchemeAndHttpHost();
        $siteUrls = LSCacheHelper::getSiteUrls($rootURL);
        $lscUrl = $rootURL . '/admin/config/development/lscache';

        $visitorIP =  $_SERVER['REMOTE_ADDR'];
        $serverIP = $_SERVER['SERVER_ADDR'];
        
        if(\Drupal::currentUser() && \Drupal::currentUser()->isAuthenticated()) {
            set_time_limit(0);
            ob_implicit_flush(TRUE);
            if (ob_get_contents()) {
                ob_end_clean();
            }
        
            echo 'LSCache Warmup Start...<br><br>' . PHP_EOL;
            echo 'Warmup Public Caches...<br><br>' . PHP_EOL;
            $this->crawlUrls($siteUrls,false);

            $config = \Drupal::config('lite_speed_cache.settings');
            $cacheStatus = $config->get('lite_speed_cache.private_cache_status');
            if($cacheStatus=='1') {
                $sessionName='';
                $sc = new SessionConfiguration();
                if($sc->hasSession($request)){
                    $options = $sc->getOptions($request);
                    $sessionName = $options['name'];
                }
                $sessionValue = $request->cookies->get($sessionName);
                $cookie = 'lsc_private=e70f67d087a65a305e80267ba3bfbc97;_lscache_vary=user%3Aloggedin;' . $sessionName . '=' . $sessionValue;
                echo '<br>Warmup Private Cache for logged-in users...<br><br>' . PHP_EOL;
                $this->crawlUrls($siteUrls,false,$cookie);
            }
            usleep(3000000);
            \Drupal::messenger()->addMessage(t('Instructed LiteSpeed Web Server to warmup this site cache!'));
            echo '<script type="text/javascript">
                       window.location = "' . str_replace('&amp;', '&', $lscUrl) . '"
                  </script>';
        }
        else if(($visitorIP=="127.0.0.1") || ($serverIP=="127.0.0.1") || ($visitorIP==$serverIP)){
            $this->crawlUrls($siteUrls, true);
        } else {
            return new HtmlResponse( ['#markup'=>'please access from localhost with "curl " command!']);
        }

        return new HtmlResponse(['#markup'=>'']);

  }


  public function showesi() {
    return [
      'content' => [
        '#markup' => 'LiteSpeed ESI Cache.',
      ],
    ] ;
  }

  private function crawlUrls($urls, $cli=false, $cookie='') {
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
        if(!empty($cookie)){
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }

        $buffer = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (in_array($httpcode, $acceptCode)) {
            echo $current . '/'. $total . ' Warm up:    ' . $url . "    success!";
        } else if($httpcode==428){
            echo "Web Server crawler feature not enabled, please check https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:enabling_the_crawler";
            break;
        } else {
            echo $current . '/'. $total . ' Warm up:    ' . $url . "    failed!";
        }

        if($cli) { echo PHP_EOL;}
        else { echo "<br>".PHP_EOL;}
        flush();

    }
  }  

}