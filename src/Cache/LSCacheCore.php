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

class LSCacheCore extends LSCacheBase
{
    private static $instance = null;

    protected $site_only_tag = "";
    
    protected $loginCachable = false;
        
    /**
     *
     *  set the specified tag for this site
     *
     * @since   1.0.0
     */
    public function __construct($tag = '')
    {
        if(!isset($tag) || ($tag=='')){
            $this->site_only_tag = substr(md5(__DIR__),0,4);
        }
        else{
            $this->site_only_tag = $tag;
        }
    }

    /**
     *
     *  purge all public cache of this site
     *
     * @since   1.0.0
     */
    public function purgeAllPublic()
    {
        $LSheader = self::CACHE_PURGE . 'public,' . $this->site_only_tag;
        $this->liteSpeedHeader($LSheader);
    }

    /**
     *
     *  purge all private cache of this session
     *
     * @since   0.1
     */
    public function purgeAllPrivate()
    {
        $LSheader = self::CACHE_PURGE . 'private,' . $this->site_only_tag;
        $this->liteSpeedHeader($LSheader);
    }

    /**
     *
     * Cache this page for public use if not cached before
     *
     * @since   1.0.1
     */
    public function cachePublic($publicTags, $esi=false)
    {
        if (!isset($publicTags) || ($publicTags == null)) {
            return;
        }

        $LSheader = self::PUBLIC_CACHE_CONTROL . $this->public_cache_timeout;
        if($esi){
            $LSheader .= ',esi=on';
        }        
        $this->liteSpeedHeader($LSheader);

        $siteTags = Array();
        array_push($siteTags, '');
        $this->tagsForSite($siteTags, $publicTags);

        $LSheader = $this->tagCommand( self::CACHE_TAG ,  $siteTags);
        $this->liteSpeedHeader($LSheader);
    }


    /**
     *
     * put tag in Array together to make an head command .
     *
     * @since   1.0.0
     */
    public function tagCommand($start, Array $tagArray){
        $cmd = $start;
        
        foreach ($tagArray as $tag) {
            $cmd .= $this->site_only_tag . $tag . ",";
        }
        return substr($cmd,0,-1);
    }
    
    
    /**
     *
     * Cache this page for private session if not cached before
     *
     * @since   0.1
     */
    public function cachePrivate($privateTags = "", $esi=false)
    {
        if ( !isset($privateTags) || ($privateTags == "") ) {
            return;
        }

        $LSheader = self::PRIVATE_CACHE_CONTROL . $this->private_cache_timeout;
        if($esi){
            $LSheader .= ',esi=on';
        }
        $this->liteSpeedHeader($LSheader);

        $siteTags = Array();
        $this->tagsForSite($siteTags, $privateTags);
        array_push($siteTags,  '');
        
        $LSheader = $this->tagCommand( self::CACHE_TAG ,  $siteTags);
        $this->liteSpeedHeader($LSheader);
    }

    public function getSiteOnlyTag(){
        return $this->site_only_tag;
    }
    
    
    public static function getInstance()
    {
        if (self::$instance == null)
        {
            self::$instance = new LSCacheCore();
        }
 
        return self::$instance;
    }
    
    public function setLoginCachable($loginCachable){
        if($loginCachable == '0'){
            $this->loginCachable = false;
        } else {
            $this->loginCachable = true;
        }
    }

    public function getLoginCachable(){
        return $this->loginCachable;
    }
    
}