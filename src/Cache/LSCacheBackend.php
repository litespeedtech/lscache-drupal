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
  const PURGE_HEAD_NAME = 'X-LiteSpeed-Purge';
  static $publicPurgeTags = [];
  static $publicPurgeAll = false;

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

      $current_path = \Drupal::service('path.current')->getPath();
      if(str_starts_with($current_path,'/user/')){
        return;
      }
            
      $config = \Drupal::config('lite_speed_cache.settings');
      $cacheStatus = $config->get('lite_speed_cache.cache_status');
      if($cacheStatus=='0' or $cacheStatus == 'Off') {
        return;
      }

      if($expire>0){
        $this->public_cache_timeout = $expire;
      } else {
        $this->public_cache_timeout = $config->get('lite_speed_cache.max_age');
      }
      
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

    /**
     * send purge header to response object
     */
    public function purgeAction(){
      if(self::$publicPurgeAll){
        $tag = 'public, ' . $this->site_only_tag;
        $this->purgePublic([$this->site_only_tag]);
        $this->logDebug();
        return $tag;
      } else if (!empty(self::$publicPurgeTags)) {
        $tags = $this->filterTags(self::$publicPurgeTags);
        if(empty($tags)){
          return false;
        }
        $tag =  'public, ' . $this->tagCommand('', $tags);
        $this->purgePublic($tags);
        $this->logDebug();
        self::$publicPurgeTags=[];
        return $tag;
      } else {
        return false;
      }
    }
 
    
    /**
     * remove general configuration tags, for those change, use purge all cache
     */
    public function filterTags($tags){
        $finalTags = [];
        foreach ($tags as $val) {
            if ((strpos($val, 'config:') !== False) or (strpos($val, 'user:') !== False) or (strpos($val, 'taxonomy_term') !== False) or (strpos($val, '_view') !== False) or (strpos($val, '_list') !== False) or ($val == "http_response") or ($val == "rendered")) {
                continue;
            }
            else{
                $finalTags[]=$val;
            }
        }
        return $finalTags;
    }


    /**
     * if enabled debug, write cache header to web server Log file.
     */
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
      self::$publicPurgeTags[]=$cid;
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
      self::$publicPurgeAll = true;
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
      $this->tagsForSite(self::$publicPurgeTags, $tags);
    }
  
    /**
     * {@inheritdoc}
     */
    public function invalidateAll() {
      self::$publicPurgeAll = true;

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
      self::$publicPurgeAll = true;
    }

    
  }
