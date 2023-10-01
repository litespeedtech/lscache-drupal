<?php

/**
 * Core function of communicating with LSWS Server for LSCache operations
 * The Core class works at site level, its operation will only affect a site in the server.
 *
 * @since      1.0.0
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 * @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @license    https://opensource.org/licenses/GPL-3.0
 */
namespace Drupal\lite_speed_cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

class LSCacheBackend extends LSCacheCore implements CacheBackendInterface, CacheTagsInvalidatorInterface {

    /**
     * {@inheritdoc}
     */
    public function get($cid, $allow_invalid = FALSE) {
        return FALSE;
    }
  
    /**
     * {@inheritdoc}
     */
    public function getMultiple(&$cids, $allow_invalid = FALSE) {
        return [];
    }
  
    /**
     * {@inheritdoc}
     */
    public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = []) {
      $config = \Drupal::config('lite_speed_cache.settings');
      $cacheStatus = $config->get('lite_speed_cache.cache_status');
      if($cacheStatus=='0' or $cacheStatus == 'Off') {
        return;
      }
      $this->public_cache_timeout = $config->get('lite_speed_cache.max_age');
      $tags = array_unique($tags);
      //$tags[] = $cid;
      $ftags = $this->filterTags($tags);
      $this->cachePublic($ftags);
      $this->logDebug($config);
    }
  
    /**
     * {@inheritdoc}
     */
    public function setMultiple(array $items = []) {
      $cacheStatus = $config->get('lite_speed_cache.cache_status');
      if($cacheStatus=='0' or $cacheStatus == 'Off') {
        return;
      }
      $this->public_cache_timeout = $config->get('lite_speed_cache.max_age');
      $tags = array();
      foreach ($items as $cid => $item) {
        //$tags[] = $cid;
        if($item['tags']){
            $tags = array_merge($tags, $item['tags']);
        }
      }
      $ftags = $this->filterTags($tags);
      $this->cachePublic($ftags);
      $this->logDebug($config);
    }
  
  
    protected function filterTags($tags){
        $finalTags = [];
        foreach ($tags as $val) {
            if ((strpos($val, 'config:') !== False) or (strpos($val, 'user:') !== False) or (strpos($val, 'taxonomy_term') !== False) or ($val == "http_response") or ($val == "rendered")) {
                continue;
            }
            else{
                $finalTags[]=$val;
            }
        }
        return $finalTags;
    }


    protected function logDebug($config=false) {
      if(!$config){
        $config = \Drupal::config('lite_speed_cache.settings');
      }
      $debug = $config->get('lite_speed_cache.debug');
      if($debug=='1' or $debug == 'On') {
        error_log($this->getLogBuffer());
      }
    }   

    /**
     * {@inheritdoc}
     */
    public function delete($cid) {
      $this->purgePublic($cid);
      $this->logDebug();
    }
  
    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(array $cids) {
      $items = array_flip($cids);
      $tags = array();
      foreach ($items as $cid => $item) {
        $tags[] = $cid;
      }
      $this->purgePublic($tags);
      $this->logDebug();
    }
  
    /**
     * {@inheritdoc}
     */
    public function deleteAll() {
        $this->purgeAllPublic();
        $this->logDebug();
      }
  
    /**
     * {@inheritdoc}
     */
    public function invalidate($cid) {
        $this->delete($cid);
    }
  
    /**
     * {@inheritdoc}
     */
    public function invalidateMultiple(array $cids) {
        $this->deleteMultiple($cids);
    }
  
    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags) {
        $this->purgePublic($tags);
        $this->logDebug($config);
    }
  
    /**
     * {@inheritdoc}
     */
    public function invalidateAll() {
        $this->purgeAllPublic();
        $this->logDebug($config);
    }
  
    /**
     * {@inheritdoc}
     */
    public function garbageCollection() {
    }
  
    /**
     * {@inheritdoc}
     */
    public function removeBin() {
        $this->purgeAllPublic();
        $this->logDebug($config);
    }

    
  }
