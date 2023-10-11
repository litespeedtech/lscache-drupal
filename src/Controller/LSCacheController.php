<?php

namespace Drupal\lite_speed_cache\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\lite_speed_cache\Cache\LSCacheCore;

class LSCacheController extends ControllerBase {

    public function purgeAll() {

        $visitorIP =  $_SERVER['REMOTE_ADDR'];
        $serverIP = $_SERVER['SERVER_ADDR'];
        
        if(($visitorIP=="127.0.0.1") || ($serverIP=="127.0.0.1") || ($visitorIP==$serverIP)){
            LSCacheCore::getInstance()->purgeAllPublic();
            $result = 'All LiteSpeed Cache purged!';
        } else {
            $result = '<h3>Access denied! <br> please access from localhost with "curl -I" command!</h3>';
        }

        header('result:'.$result);

        return array(
            '#type' => 'markup',
            '#markup' => $this->t($result),
        );
    }

}