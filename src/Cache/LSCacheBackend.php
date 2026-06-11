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
  static $publicPurgeTags = [];
  static $privatePurgeTags = [];
  static $publicPurgeAll = false;
  static $privatePurgeAll = false;

  public $cacheStatus = 0;
  public $ncookies = '';

  public function __construct(){
    parent::__construct();
    $config = \Drupal::config('lite_speed_cache.settings');
    $this->cacheStatus = $config->get('lite_speed_cache.cache_status');    
    $this->public_cache_timeout = $config->get('lite_speed_cache.max_age');
    $this->private_cache_timeout = $config->get('lite_speed_cache.private_max_age');
    $this->ncookies = $config->get('lite_speed_cache.nocache_cookies');
  }
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

      if($this->cacheStatus=='0' or $this->cacheStatus == 'Off') {
        return;
      }

      if($this->ncookies && $nc=explode(',',$this->ncookies)){
        foreach(nc as $ncookie){
          if($_COOKIE[$ncookie]){
            $this->checkVary("NoCache");
            return;
          }
        }
      }

      if($expire>0){
        $this->public_cache_timeout = $expire;
      }
      
      $isPrivate = false;
      $cachemeta = $data->getCacheableMetadata();
      $contexts = $cachemeta->getCacheContexts();
      $isPrivate = in_array('user.roles:authenticated',$contexts);
      if($isPrivate && ($expire>0)){
        $this->private_cache_timeout = $expire;
      }

      $cacheMaxAge = $cachemeta->getCacheMaxAge();
      if($cacheMaxAge>0){
        if($isPrivate){
          $this->private_cache_timeout = $cacheMaxAge;
        } else {
          $this->public_cache_timeout = $cacheMaxAge;
        }
      }
      $tags = array_unique($tags);
      $ftags = $this->filterTags($tags);

      if($ftags){
        if($isPrivate){
          $this->cachePrivate($ftags);
        } else {
          $this->cachePublic($ftags);
        }
      }
      
      $this->logDebug();
    }
  

    /**
     * {@inheritdoc}
     */
    public function setMultiple(array $items = []) {
      if($this->cacheStatus=='0' or $this->cacheStatus == 'Off') {
        return;
      }

      if($this->ncookies && $nc=explode(',',$this->ncookies)){
        foreach(nc as $ncookie){
          if($_COOKIE[$ncookie]){
            $this->checkVary("NoCache");
            return;
          }
        }
      }

      $tags = array();
      foreach ($items as $cid => $item) {
        //$tags[] = $cid;
        if($item['tags']){
            $tags = array_merge($tags, $item['tags']);
        }
      }
      $ftags = $this->filterTags($tags);
      $this->cachePublic($ftags);
      $this->logDebug();
    }

    /**
     * send purge header to response object
     */
    public function purgeAction(){
      $actions = [];
      if(self::$publicPurgeAll){
        $actions[] = parent::purgeAllPublic();
        $this->logDebug();
      }
      
      if(self::$privatePurgeAll){
        $actions[] = parent::purgeAllPrivate();
        $this->logDebug();
      }
      
      if (!self::$publicPurgeAll && !empty(self::$publicPurgeTags)) {
          $tags = $this->filterTags(self::$publicPurgeTags);
          if(!empty($tags)){
            $actions[] =  $this->purgePublic($tags);
            $this->logDebug();
            self::$publicPurgeTags=[];
          }
      }

      if (!self::$privatePurgeAll && !empty(self::$privatePurgeTags)) {
          $tags = $this->filterTags(self::$privatePurgeTags);
          if(!empty($tags)){
            $actions[] = $this->purgePrivate($tags);
            $this->logDebug();
            self::$privatePurgeTags=[];
          }
      }
      return $actions;
    }

    public function invalidatePrivate($tags) {
      self::$privatePurgeTags[]=$tags;
      $this->logDebug();
    }
  
    public function purgeAllPublic() {
        self::$publicPurgeAll = true;
    }

    public function purgeAllPrivate() {
        self::$privatePurgeAll = true;
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
      self::$privatePurgeAll = true;
    }

    
  }
