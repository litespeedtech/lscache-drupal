<?php
namespace Drupal\lite_speed_cache\EventSubscriber;

use Drupal\lite_speed_cache\Cache\LSCacheBackend;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class LSCacheSubscriber implements EventSubscriberInterface {

  /**
   * Sets extra HTTP headers.
   */
  public function onRespond(ResponseEvent $event) {
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
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond', -100];
    return $events;
  }

}