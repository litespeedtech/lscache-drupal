<?php

namespace Drupal\lite_speed_cache\Event;

use Drupal\Component\EventDispatcher\Event;

final class LSCacheEvent extends Event{
  const PURGE_ALL_PUBLIC = 'lite_speed_cache.purge_all_public';
  const PURGE_ALL_PRIVATE = 'lite_speed_cache.purge_all_private';
}