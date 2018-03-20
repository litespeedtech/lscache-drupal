<?php
/**
 * Created by PhpStorm.
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 * Date: 12/20/17
 * Time: 11:27 PM
 */

namespace Drupal\lite_speed_cache\StackMiddleware;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Core\Site\Settings;
use Drupal\user\Authentication\Provider\Cookie;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;


class LiteSpeedCache implements HttpKernelInterface {

    /**
     * The wrapped HTTP kernel.
     *
     * @var \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    protected $httpKernel;

    /**
     * The cache bin.
     *
     * @var \Drupal\Core\Cache\CacheBackendInterface.
     */
    protected $cache;

    /**
     * A policy rule determining the cacheability of a request.
     *
     * @var \Drupal\Core\PageCache\RequestPolicyInterface
     */
    protected $requestPolicy;

    /**
     * A policy rule determining the cacheability of the response.
     *
     * @var \Drupal\Core\PageCache\ResponsePolicyInterface
     */
    protected $responsePolicy;


    /**
     * Name of LiteSpeed Page Cache's response header.
     */
    const LSCACHE = 'X-LiteSpeed-Cache-Control';

    /**
     * Constructs a PageCache object.
     *
     * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
     *   The decorated kernel.
     * @param \Drupal\Core\Cache\CacheBackendInterface $cache
     *   The cache bin.
     * @param \Drupal\Core\PageCache\RequestPolicyInterface $request_policy
     *   A policy rule determining the cacheability of a request.
     * @param \Drupal\Core\PageCache\ResponsePolicyInterface $response_policy
     *   A policy rule determining the cacheability of the response.
     */
    public function __construct(HttpKernelInterface $http_kernel, CacheBackendInterface $cache, RequestPolicyInterface $request_policy, ResponsePolicyInterface $response_policy) {
        $this->httpKernel = $http_kernel;
        $this->cache = $cache;
        $this->requestPolicy = $request_policy;
        $this->responsePolicy = $response_policy;
    }

    /**
     * {@inheritdoc}
     * Filter un-important tags!
     */

    private function filterTags($tags){
        $finalTags = [];

        $commonTag = substr(md5(DRUPAL_ROOT),0,5);

        foreach ($tags as $val) {
            if (strpos($val, 'config') !== false or ($val == "http_response") or ($val == "rendered")) {
                continue;
            }
            else{
                array_push($finalTags,$commonTag . '_' .$val);
            }
        }
        return $finalTags;
    }

    /**
     * {@inheritdoc}
     */

    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {



        if( (isset($_SERVER['X-LSCACHE']) && $_SERVER['X-LSCACHE']) || (isset($_SERVER['HTTP_X_LSCACHE']) && $_SERVER['HTTP_X_LSCACHE']) ){

            // Only allow page caching on master request.
            if ($type === static::MASTER_REQUEST && $this->requestPolicy->check($request) === RequestPolicyInterface::ALLOW) {
                $response = $this->lookup($request, $type, $catch);
            }
            else {
                $response = $this->pass($request, $type, $catch);
            }

        }else{
            $response = $this->pass($request, $type, $catch);
        }




        return $response;
    }

    /**
     * Sidesteps the page cache and directly forwards a request to the backend.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   A request object.
     * @param int $type
     *   The type of the request (one of HttpKernelInterface::MASTER_REQUEST or
     *   HttpKernelInterface::SUB_REQUEST)
     * @param bool $catch
     *   Whether to catch exceptions or not
     *
     * @returns \Symfony\Component\HttpFoundation\Response $response
     *   A response object.
     */
    protected function pass(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
        return $this->httpKernel->handle($request, $type, $catch);
    }

    /**
     * Fetch request from backend and set cache headers
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   A request object.
     * @param int $type
     *   The type of the request (one of HttpKernelInterface::MASTER_REQUEST or
     *   HttpKernelInterface::SUB_REQUEST)
     * @param bool $catch
     *   Whether to catch exceptions or not
     *
     * @returns \Symfony\Component\HttpFoundation\Response $response
     *   A response object.
     */
    protected function lookup(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {


        // fetch request from backend and set cache headers

        $response = $this->fetch($request, $type, $catch);

        if (!$response instanceof CacheableResponseInterface) {
            return;
        }

        // Only allow caching in the browser and prevent that the response is stored
        // by an external proxy server when the following conditions apply:
        // 1. There is a session cookie on the request.
        // 2. The Vary: Cookie header is on the response.
        // 3. The Cache-Control header does not contain the no-cache directive.
        if ($request->cookies->has(session_name()) &&
            in_array('Cookie', $response->getVary()) &&
            !$response->headers->hasCacheControlDirective('no-cache')) {
            $response->setPrivate();
        }

        // Perform HTTP revalidation.
        // @todo Use Response::isNotModified() as
        //   per https://www.drupal.org/node/2259489.
        $last_modified = $response->getLastModified();
        if ($last_modified) {
            // See if the client has provided the required HTTP headers.
            $if_modified_since = $request->server->has('HTTP_IF_MODIFIED_SINCE') ? strtotime($request->server->get('HTTP_IF_MODIFIED_SINCE')) : FALSE;
            $if_none_match = $request->server->has('HTTP_IF_NONE_MATCH') ? stripslashes($request->server->get('HTTP_IF_NONE_MATCH')) : FALSE;

            if ($if_modified_since && $if_none_match
                // etag must match.
                && $if_none_match == $response->getEtag()
                // if-modified-since must match.
                && $if_modified_since == $last_modified->getTimestamp()) {
                $response->setStatusCode(304);
                $response->setContent(NULL);

                // In the case of a 304 response, certain headers must be sent, and the
                // remaining may not (see RFC 2616, section 10.3.5).
                foreach (array_keys($response->headers->all()) as $name) {
                    if (!in_array($name, ['content-location', 'expires', 'cache-control', 'vary'])) {
                        $response->headers->remove($name);
                    }
                }
            }
        }

        return $response;
    }

    /**
     * Fetches a response from the backend and stores it in the cache.
     *
     * @see drupal_page_header()
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   A request object.
     * @param int $type
     *   The type of the request (one of HttpKernelInterface::MASTER_REQUEST or
     *   HttpKernelInterface::SUB_REQUEST)
     * @param bool $catch
     *   Whether to catch exceptions or not
     *
     * @returns \Symfony\Component\HttpFoundation\Response $response
     *   A response object.
     */
    protected function fetch(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->httpKernel->handle($request, $type, $catch);

        // Only set the 'X-Drupal-Cache' header if caching is allowed for this
        // response.

        if (!$response instanceof CacheableResponseInterface) {
            return;
        }

        // This plugin config object

        $config = \Drupal::config('lite_speed_cache.settings');


        // Drupal site config object

        $maxAage = $config->get('lite_speed_cache.max_age');
        $commonTag = substr(md5(DRUPAL_ROOT),0,5);


        // this determines if response is storeable
        if ($this->checkCacheAbility($request, $response)) {
            $cacheStatus = $config->get('lite_speed_cache.cache_status');
            if($cacheStatus=='0' or $cacheStatus == 'On') {
                $response->headers->set(LiteSpeedCache::LSCACHE, 'public, max-age=' . $maxAage);
                $tags = $response->getCacheableMetadata()->getCacheTags();
                $tags = $this->filterTags($tags);
                array_push($tags,$commonTag);
                $tags = implode(',', $tags);
                $response->headers->set('X-LiteSpeed-Tag', $tags);
            }
        }
        return $response;
    }

    /**
     * Stores a response in the page cache.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   A request object.
     * @param \Symfony\Component\HttpFoundation\Response $response
     *   A response object that should be stored in the page cache.
     *
     * @returns bool
     */
    protected function checkCacheAbility(Request $request, Response $response) {

        if (!$response instanceof CacheableResponseInterface) {
            return FALSE;
        }

        // Currently it is not possible to cache binary file or streamed responses:
        // https://github.com/symfony/symfony/issues/9128#issuecomment-25088678.
        // Therefore exclude them, even for subclasses that implement
        // CacheableResponseInterface.
        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return FALSE;
        }

        // Allow policy rules to further restrict which responses to cache.
        if ($this->responsePolicy->check($response, $request) === ResponsePolicyInterface::DENY) {
            return FALSE;
        }

        return TRUE;
    }


}