<?php

namespace Middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Cache\CacheItemPoolInterface;
use Micheh\Cache\CacheUtil;

class Cache implements MiddlewareInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * Set the PSR-6 cache pool.
     *
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Process a request and return a response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface      $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        //Only GET & HEAD request
        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return $delegate->process($request);
        }

        $util = new CacheUtil();
        $key = $request->getMethod().md5((string) $request->getUri());
        $item = $this->cache->getItem($key);

        //It's cached
        if ($item->isHit()) {
            $headers = $item->get();
            $response = Utils\Factory::createResponse(304);

            foreach ($headers as $name => $header) {
                $response = $response->withHeader($name, $header);
            }

            if ($util->isNotModified($request, $response)) {
                return $response;
            }

            $this->cache->deleteItem($key);
        }

        $response = $delegate->process($request);

        if (!$response->hasHeader('Last-Modified')) {
            $response = $util->withLastModified($response, time());
        }

        //Save in the cache
        if ($util->isCacheable($response)) {
            $item->set($response->getHeaders());
            $item->expiresAfter($util->getLifetime($response));

            $this->cache->save($item);
        }

        return $response;
    }
}
