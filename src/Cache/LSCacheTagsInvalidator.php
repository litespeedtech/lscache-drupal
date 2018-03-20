<?php
/**
 * Created by PhpStorm.
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 * Date: 12/26/17
 * Time: 7:25 PM
 */

namespace Drupal\lite_speed_cache\Cache;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;


class LSCacheTagsInvalidator implements CacheTagsInvalidatorInterface {

    /**
     * Variable to store tags to invalidate when $cacheCheck = 1
     */

    public static $tags;

    /**
     * Variable to store tags to invalidate when $cacheCheck = 1
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

        $finalTags = [];

        $commonTag = substr(md5(DRUPAL_ROOT),0,5);

        foreach ($tags as $val) {
            if (strpos($val, 'config') !== false or ($val == "http_response") or ($val == "rendered")) {
                LSCacheTagsInvalidator::$purgeAllByTags = 1;
                return;
            }
            else{
                array_push($finalTags,$commonTag . '_' .$val);
            }
        }

        LSCacheTagsInvalidator::$tags = $finalTags;
        LSCacheTagsInvalidator::$cacheCheck = 1;
    }


}