<?php
/**
 * Created by PhpStorm.
 * User: usman
 * Date: 12/20/17
 * Time: 2:23 PM
 */

namespace Drupal\lite_speed_cache\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Core\Render\RenderCacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use \Drupal\lite_speed_cache\Cache\LSCacheTagsInvalidator;
use \Drupal\lite_speed_cache\Form\LSCacheForm;
use Symfony\Component\HttpFoundation\Cookie;



class LiteSpeedCacheSubscriber implements EventSubscriberInterface {
    /**
     * Name of LiteSpeed Page Cache's Status response header.
     */
    const STATUSHEADER = 'X-LS-PURGE-STATUS';

    /**
     * Name of LiteSpeed Page Cache's Purge response header.
     */
    const PURGEHEADER = 'X-LiteSpeed-Purge';

    /**
     * Name of LiteSpeed Dynamic Page Cache's Status header.
     */
    const DYNAMICSTATUSHEADER = 'X-LiteSpeed-Dynamic-Status';

    /**
     * A request policy rule determining the cacheability of a response.
     *
     * @var \Drupal\Core\PageCache\RequestPolicyInterface
     */
    protected $requestPolicy;

    /**
     * A response policy rule determining the cacheability of the response.
     *
     * @var \Drupal\Core\PageCache\ResponsePolicyInterface
     */
    protected $responsePolicy;

    /**
     * The render cache.
     *
     * @var \Drupal\Core\Render\RenderCacheInterface
     */
    protected $renderCache;

    /**
     * The renderer configuration array.
     *
     * @var array
     */
    protected $rendererConfig;

    /**
     * Dynamic Page Cache's redirect render array.
     *
     * @var array
     */
    protected $dynamicPageCacheRedirectRenderArray = [
        '#cache' => [
            'keys' => ['response'],
            'contexts' => [
                'route',
                // Some routes' controllers rely on the request format (they don't have
                // a separate route for each request format). Additionally, a controller
                // may be returning a domain object that a KernelEvents::VIEW subscriber
                // must turn into an actual response, but perhaps a format is being
                // requested that the subscriber does not support.
                // @see \Drupal\Core\EventSubscriber\RenderArrayNonHtmlSubscriber::onResponse()
                'request_format',
            ],
            'bin' => 'dynamic_page_cache',
        ],
    ];

    /**
     * Internal cache of request policy results.
     *
     * @var \SplObjectStorage
     */
    protected $requestPolicyResults;

    /**
     * Constructs a new DynamicPageCacheSubscriber object.
     *
     * @param \Drupal\Core\PageCache\RequestPolicyInterface $request_policy
     *   A policy rule determining the cacheability of a request.
     * @param \Drupal\Core\PageCache\ResponsePolicyInterface $response_policy
     *   A policy rule determining the cacheability of the response.
     * @param \Drupal\Core\Render\RenderCacheInterface $render_cache
     *   The render cache.
     * @param array $renderer_config
     *   The renderer configuration array.
     */

    public function __construct(RequestPolicyInterface $request_policy, ResponsePolicyInterface $response_policy, RenderCacheInterface $render_cache, array $renderer_config) {
        $this->requestPolicy = $request_policy;
        $this->responsePolicy = $response_policy;
        $this->renderCache = $render_cache;
        $this->rendererConfig = $renderer_config;
        $this->requestPolicyResults = new \SplObjectStorage();
    }

    /**
     * Sets a response in case of a Dynamic Page Cache hit.
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     *   The event to process.
     */
    public function onRouteMatch(GetResponseEvent $event) {

        // Don't cache the response if the Dynamic Page Cache request policies are
        // not met. Store the result in a static keyed by current request, so that
        // onResponse() does not have to redo the request policy check.
        $request = $event->getRequest();
        $request_policy_result = $this->requestPolicy->check($request);
        $this->requestPolicyResults[$request] = $request_policy_result;
        if ($request_policy_result === RequestPolicyInterface::DENY) {
            return;
        }
    }

    /**
     * {@inheritdoc}
     * Filter un-important tags!
     */

    private function filterTags($tags){
        $finalTags = [];
        foreach ($tags as $val) {
            if (strpos($val, 'config') !== false) {
                continue;
            }
            else{
                array_push($finalTags,$val);
            }
        }
        return $finalTags;
    }

    /**
     * Stores a response in case of a Dynamic Page Cache miss, if cacheable.
     *
     * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
     *   The event to process.
     */
    public function onResponse(FilterResponseEvent $event){
        $response = $event->getResponse();
        $request = $event->getRequest();

        // This plugin config object

        $config = \Drupal::config('lite_speed_cache.settings');

        $lsCacheDebug = $config->get('lite_speed_cache.debug');
        $maxAgePrivate = $config->get('lite_speed_cache.max_age_private');
        $cacheStatus = $config->get('lite_speed_cache.cache_status');

        // Drupal site config object

        $mainConfig = \Drupal::config('system.site');

        $siteName = $mainConfig->get('name');

        // Dynamic Page Cache only works with cacheable responses. It does not work
        // with plain Response objects. (Dynamic Page Cache needs to be able to
        // access and modify the cacheability metadata associated with the
        // response.)

        if (!$response instanceof CacheableResponseInterface) {
            return;
        }

        // Check if tags based invalidation is triggered and its purge all tag

        if (LSCacheTagsInvalidator::$purgeAllByTags) {
            if($lsCacheDebug=='0' or $lsCacheDebug == 'On') {
                $response->headers->set(self::STATUSHEADER, 'LS Cache Purged!');
            }
            $response->headers->set(LiteSpeedCacheSubscriber::PURGEHEADER, $siteName);
        } else {
            if($lsCacheDebug=='0' or $lsCacheDebug == 'On') {
                $response->headers->set(self::STATUSHEADER, 'No Purge!');
            }
        }

        // Check if tags based invalidation is triggered

        if (LSCacheTagsInvalidator::$cacheCheck) {
            if($lsCacheDebug=='0' or $lsCacheDebug == 'On') {
                $response->headers->set(self::STATUSHEADER, 'LS Cache Purged!');
            }
            $tags = implode(",", LSCacheTagsInvalidator::$tags);
            $response->headers->set(LiteSpeedCacheSubscriber::PURGEHEADER, $tags);
        } else {
            if($lsCacheDebug=='0' or $lsCacheDebug == 'On') {
                $response->headers->set(self::STATUSHEADER, 'No Purge!');
            }
        }

        // Check if purge all invalidation is triggered

        if (LSCacheForm::$purgeALL) {
            if($lsCacheDebug=='0' or $lsCacheDebug == 'On') {
                $response->headers->set(self::STATUSHEADER, 'LS Cache Purged!');
            }
            $response->headers->set(LiteSpeedCacheSubscriber::PURGEHEADER, "*");
        } else {
            if($lsCacheDebug=='0' or $lsCacheDebug == 'On') {
                $response->headers->set(self::STATUSHEADER, 'No Purge');
            }
        }

        // Check if only this site is purged

        if (LSCacheForm::$purgeThisSite) {
            if($lsCacheDebug=='0' or $lsCacheDebug == 'On') {
                $response->headers->set(self::STATUSHEADER, 'LS Cache Purged!');
            }
            $response->headers->set(LiteSpeedCacheSubscriber::PURGEHEADER, $siteName);
        } else {
            if($lsCacheDebug=='0' or $lsCacheDebug == 'On') {
                $response->headers->set(self::STATUSHEADER, 'No Purge');
            }
        }

        if ($request->cookies->has(session_name())) {

            if ($request->cookies->get('_lscache_vary') != 'loggedin') {

                $cookie = new Cookie('_lscache_vary', 'loggedin');
                $response->headers->setCookie($cookie);

                $cookie = new Cookie('lsc_private', $request->cookies->get(session_name()));
                $response->headers->setCookie($cookie);
            }

            // There's no work left to be done if this is an uncacheable response.
            if (!$this->shouldCacheResponse($response)) {
                // The response is uncacheable, mark it as such.
                $response->headers->set(self::DYNAMICSTATUSHEADER, 'UNCACHEABLE');
                return;
            }


            $request = $event->getRequest();
            if (!isset($this->requestPolicyResults[$request])) {
                return;
            }


            if ($this->requestPolicyResults[$request] === RequestPolicyInterface::DENY || $this->responsePolicy->check($response, $request) === ResponsePolicyInterface::DENY) {
                    return;
            }


            if($cacheStatus=='0' or $cacheStatus == 'On')  {
                $response->headers->set('X-LiteSpeed-Cache-Control', 'private, max-age='.$maxAgePrivate);
                $tags = $response->getCacheableMetadata()->getCacheTags();
                $tags = $this->filterTags($tags);
                array_push($tags,$siteName);
                $tags = implode(', ', $tags);
                $response->headers->set('X-LiteSpeed-Tag', $tags);
            }


        }else {
                $cookies = $request->cookies;
                if($cookies->has('_lscache_vary')) {
                    $response->headers->clearCookie('_lscache_vary');
                }
                if($cookies->has('lsc_private')) {
                    $response->headers->clearCookie('lsc_private');
                }
            }

    }


    /**
     * Whether the given response should be cached by LiteSpeed Page Cache.
     *
     * @param \Drupal\Core\Cache\CacheableResponseInterface $response
     *   The response whose cacheability to analyze.
     *
     * @return bool
     *   Whether the given response should be cached.
     *
     * @see \Drupal\Core\Render\Renderer::shouldAutomaticallyPlaceholder()
     */
    protected function shouldCacheResponse(CacheableResponseInterface $response) {
        $conditions = $this->rendererConfig['auto_placeholder_conditions'];

        $cacheability = $response->getCacheableMetadata();

        // Response's max-age is at or below the configured threshold.
        if ($cacheability->getCacheMaxAge() !== Cache::PERMANENT && $cacheability->getCacheMaxAge() <= $conditions['max-age']) {
            return FALSE;
        }

        // Response has a high-cardinality cache context.
        if (array_intersect($cacheability->getCacheContexts(), $conditions['contexts'])) {
            return FALSE;
        }

        // Response has a high-invalidation frequency cache tag.
        if (array_intersect($cacheability->getCacheTags(), $conditions['tags'])) {
            return FALSE;
        }

        return TRUE;
    }


    /**
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        $events = [];

        // Run after AuthenticationSubscriber (necessary for the 'user' cache
        // context; priority 300) and MaintenanceModeSubscriber (Dynamic Page Cache
        // should not be polluted by maintenance mode-specific behavior; priority
        // 30), but before ContentControllerSubscriber (updates _controller, but
        // that is a no-op when Dynamic Page Cache runs; priority 25).
        $events[KernelEvents::REQUEST] = ['onRouteMatch'];

        // Run before HtmlResponseSubscriber::onRespond(), which has priority 0.
        $events[KernelEvents::RESPONSE] = ['onResponse'];

        return $events;
    }
}