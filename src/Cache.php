<?php
declare(strict_types = 1);

namespace Middlewares;

use Micheh\Cache\CacheUtil;
use Middlewares\Utils\Factory;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Cache implements MiddlewareInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * Set the PSR-6 cache pool.
     */
    public function __construct(CacheItemPoolInterface $cache, ResponseFactoryInterface $responseFactory = null)
    {
        $this->cache = $cache;
        $this->responseFactory = $responseFactory ?: Factory::getResponseFactory();
    }

    /**
     * Process a request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //Only GET & HEAD request
        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return $handler->handle($request);
        }

        $util = new CacheUtil();
        $key = $request->getMethod().md5((string) $request->getUri());
        $item = $this->cache->getItem($key);

        //It's cached
        if ($item->isHit()) {
            $headers = $item->get();
            $response = $this->responseFactory->createResponse(304);

            foreach ($headers as $name => $header) {
                $response = $response->withHeader($name, $header);
            }

            if ($util->isNotModified($request, $response)) {
                return $response;
            }

            $this->cache->deleteItem($key);
        }

        $response = $handler->handle($request);

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
