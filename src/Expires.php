<?php

namespace Middlewares;

use DateTime;
use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;
use Micheh\Cache\CacheUtil;
use Micheh\Cache\Header\ResponseCacheControl;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Expires implements MiddlewareInterface
{
    /**
     * @var string
     */
    private $default = '+1 month';

    /**
     * @var array
     */
    private $expires;

    /**
     * Define de available expires.
     *
     * @param array|null $expires
     */
    public function __construct(array $expires = null)
    {
        $this->expires = $expires ?: require __DIR__.'/expires_defaults.php';
    }

    /**
     * Set the default expires value.
     *
     * @param string $expires
     *
     * @return self
     */
    public function defaultExpires($expires)
    {
        $this->default = $expires;

        return $this;
    }

    /**
     * Process a request and return a response.
     *
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler)
    {
        $response = $handler->handle($request);

        //Only GET & HEAD request
        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return $response;
        }

        $util = new CacheUtil();

        //Only cacheable responses
        if (!$util->isCacheable($response)) {
            return $response;
        }

        //Check if a lifetime is defined
        $lifetime = $util->getLifetime($response);

        if ($lifetime !== null) {
            return self::withExpires($response, $util, '@'.(time() + $lifetime));
        }

        //Check the content type
        $contentType = $response->getHeaderLine('Content-Type');

        if ($contentType !== '') {
            foreach ($this->expires as $mime => $time) {
                if (stripos($contentType, $mime) !== false) {
                    return self::withExpires($response, $util, $time);
                }
            }
        }

        //Add default value
        return self::withExpires($response, $util, $this->default);
    }

    /**
     * Add the Expires and Cache-Control headers.
     *
     * @param ResponseInterface $response
     * @param CacheUtil         $util
     * @param string            $expires
     *
     * @return ResponseInterface $response
     */
    private static function withExpires(ResponseInterface $response, CacheUtil $util, $expires)
    {
        $expires = new DateTime($expires);
        $cacheControl = ResponseCacheControl::fromString($response->getHeaderLine('Cache-Control'))
            ->withMaxAge($expires->getTimestamp() - time());

        $response = $util->withExpires($response, $expires);

        return $util->withCacheControl($response, $cacheControl);
    }
}
