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
use Drupal\Core\Cache\CacheFactory;


class LSCacheBackendFactory extends CacheFactory {

  protected $bins = [];
  
    /**
     * {@inheritdoc}
     */
    public function get($bin) {

      if($bin!=='page'){
        return parent::get($bin);
      }
      
      if (isset($this->bins[$bin])) {
          return $this->bins[$bin];
      }
      
      $this->bins[$bin] = new LSCacheBackend();
      return $this->bins[$bin];
    }
  
  }
