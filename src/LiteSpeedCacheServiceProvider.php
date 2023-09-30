<?php

namespace Drupal\lite_speed_cache;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;


/**
 * Modifies the cache_factory service.
 */
class LiteSpeedCacheServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {  
    if ($container->hasDefinition('cache_factory')) {
      $definition = $container->getDefinition('cache_factory');
      $definition->setClass('Drupal\lite_speed_cache\Cache\LSCacheBackendFactory');
    }
  }

}