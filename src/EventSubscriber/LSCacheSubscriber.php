<?php
namespace Drupal\lite_speed_cache\EventSubscriber;

use Drupal\lite_speed_cache\Cache\LSCacheBackend;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Cache\CacheableResponseInterface;

class LSCacheSubscriber implements EventSubscriberInterface {

  /**
   * Sets extra HTTP headers.
   */
  public function onRespond($event) {
    if($event instanceof ResponseEvent){
      if (!$event->isMainRequest()) {
        return;
      }
    } else {
      if (!$event->isMasterRequest()) {
        return;
      }
    }

    $response = $event->getResponse();
    $lscInstance = new LSCacheBackend();

    $purgeTag = $lscInstance->purgeAction();

    if ($purgeTag) {
        $response->headers->set(LSCacheBackend::PURGE_HEAD_NAME, $purgeTag);
    }

    if($this->isESIrequest()){
      $this->handleESI($response);
    } else {
      $this->handleESIblocks($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond', -999];
    return $events;
  }


  protected function handleESIblocks($response){
    if(!($response instanceof CacheableResponseInterface)){
      return;
    }
    $cache = $response->getCacheableMetadata();
    if( $cache->getCacheMaxAge() < 0 ){
      return;
    }


    if($response->getStatusCode()!=200){
      return;
    }
    //not logged in
    if(empty(\Drupal::currentUser()) || !\Drupal::currentUser()->isAuthenticated()) {
      return;
    }

    $content = $response->getContent();
    if(empty($content)){
      return;
    }

    $config = \Drupal::config('lite_speed_cache.settings');
    $cacheStatus = $config->get('lite_speed_cache.private_cache_status');
    if($cacheStatus=='0' or $cacheStatus == 'Off') {
      return;
    }

    $esi_blocks = $config->get('lite_speed_cache.esi_blocks');
    if(empty($esi_blocks)){
      return;
    }

    if($this->isAdminRoute()){
      return;
    }

    $dom = new \DOMDocument();
    $dom->validateOnParse = true;  
    $dom->loadHTML($content);

    $blocks = explode(PHP_EOL, $esi_blocks);
    $blockElement = false;
    foreach($blocks as $block){
      if(str_starts_with(trim($block),'id=')){
        $blockID = substr(trim($block), 3);
        $blockElement = $dom->getElementById($blockID);
        if(empty($blockElement)){ 
          continue;
        }
        $esiElement = $this->getESIelement($dom, $blockID);
        $blockElement->parentNode->replaceChild($esiElement,$blockElement);
      }
    }

    if(empty($blockElement)){
      return;
    }
    $newContent = $dom->saveHTML();
    $response->setContent($newContent);
    $lscInstance = new LSCacheBackend();

    if($cache->getCacheMaxAge()>0){
      $maxAge = $cache->getCacheMaxAge();
    } else {
      $maxAge = $config->get('lite_speed_cache.max_age');
    }
    
    $response->headers->set('X-LiteSpeed-Cache-Control', 'esi=on, public, max-age='. $maxAge);
    $tags = $response->getCacheableMetadata()->getCacheTags();
    $tags = $lscInstance->filterTags($tags);
    array_unshift($tags, '');
    $ftags = $lscInstance->tagCommand('public, ',$tags);
    $response->headers->set('X-LiteSpeed-Tag', $ftags);
    $lscInstance->checkVary("user:loggedin");
  }


  protected function handleESI($response){
    $blockID = \Drupal::request()->query->get('id');
    if(empty($blockID)){
      return;
    }

    $dom = new \DOMDocument();
    $dom->loadHTML($response->getContent());

    $blockElement = $dom->getElementById($blockID);
    if(empty($blockElement)){
      $response->setContent("<body><p>ESI Block " . $blockID . " does not exist!</p></body>");
      return;
    }

    $newContent = '<body>' . $dom->saveHTML($blockElement) . '</body>';
    $response->setContent($newContent);

    $config = \Drupal::config('lite_speed_cache.settings');
    $cacheStatus = $config->get('lite_speed_cache.private_cache_status');
    if($cacheStatus=='0' or $cacheStatus == 'Off') {
      return;
    }
    $maxAge = $config->get('lite_speed_cache.private_max_age');
    $response->headers->set('X-LiteSpeed-Cache-Control', 'esi=on, private, max-age='. $maxAge);
    $lscInstance = new LSCacheBackend();
    $ftags = $lscInstance->tagCommand('private,',  ['',$blockID]);
    $response->headers->set('X-LiteSpeed-Tag', $ftags);
    $lscInstance->checkPrivateCookie();

}

  
  protected function isESIrequest(){
    $current_uri = \Drupal::request()->getRequestUri();
    if(str_starts_with($current_uri,'/lscesi')){
      return true;
    }
    return false;
  }

  protected function isAdminRoute(){
    $route = \Drupal::routeMatch()->getRouteObject();
    $is_admin = FALSE;
    if (!empty($route)) {
      $is_admin_route = \Drupal::service('router.admin_context')->isAdminRoute($route);
      $has_node_operation_option = $route->getOption('_node_operation_route');
      $is_user_route = str_starts_with($route->getPath(), '/user/') ;
      $is_admin = ($is_admin_route || $has_node_operation_option || $is_user_route);
    }
    else {
      $current_path = \Drupal::service('path.current')->getPath();
      if(preg_match('/node\/(\d+)\/edit/', $current_path, $matches)) {
        $is_admin = TRUE;
      }
      elseif(preg_match('/taxonomy\/term\/(\d+)\/edit/', $current_path, $matches)) {
        $is_admin = TRUE;
      }
      elseif( str_starts_with($current_path, '/user/')) {
        $is_admin = TRUE;
      }
    }
    return $is_admin;
  }

  protected function getESIelement($dom, $blockID){
    $link = '/lscesi?id=' . $blockID;
    $element= $dom->createElement('esi:include');
    $element->setAttribute('src', $link);
    $element->setAttribute('cache-control', 'private');
    return $element;
  }

}