<?php
/**
 * Created by PhpStorm.
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 * Date: 12/26/17
 * Time: 7:25 PM
 */

namespace Drupal\lite_speed_cache\Cache;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;


class LSCacheTagsInvalidator implements CacheTagsInvalidatorInterface {

    /**
     * Variable to store tags to invalidate when $cacheCheck = 1
     */

    public static $tags = array();

    /**
     * If $purgeAllByTags = 1 LiteSpeedCacheSubscriber will invalidate whole website with $commonTag
     */

    public static $purgeAllByTags;

    /**
     * If $cacheCheck = 1 LiteSpeedCacheSubscriber will invalidate tags inside $tags
     */

    public static $cacheCheck;

    /**
     * Function to populate LSCacheTagsInvalidator::$tags and LSCacheTagsInvalidator::$cacheCheck
     */

    public function invalidateTags(array $tags) {

        // check if its purge all tag

        $commonTag = substr(md5(DRUPAL_ROOT),0,5);

        if (LSCacheTagsInvalidator::$purgeAllByTags) {
          return;
        }
        else {
          foreach ($tags as $val) {
              if (strpos($val, 'config') !== false or ($val == "http_response") or ($val == "rendered")) {
                  LSCacheTagsInvalidator::$purgeAllByTags = 1;
                  LSCacheTagsInvalidator::$cacheCheck = 0;
                  return;
              }
              else{
                  array_push(LSCacheTagsInvalidator::$tags,$commonTag . '_' .$val);
              }
          }
        }

        LSCacheTagsInvalidator::$cacheCheck = 1;
    }

}
