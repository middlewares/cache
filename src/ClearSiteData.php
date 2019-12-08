<?php
declare(strict_types = 1);

namespace Middlewares;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ClearSiteData implements MiddlewareInterface
{
    /**
     * @var array
     */
    private $types = ['*'];

    public function __construct(string ...$types)
    {
        if ($types) {
            $invalidTypes = array_diff($types, ['cache', 'cookies', 'storage', 'executionContext']);

            if ($invalidTypes) {
                throw new InvalidArgumentException(
                    sprintf('Invalid types to Clear-Site-Data: %s', \implode(', ', $invalidTypes))
                );
            }

            $this->types = $types;
        }
    }

    /**
     * Process a request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $types = array_map(function ($type) {
            return "\"{$type}\"";
        }, $this->types);

        $value = implode(' ', $types);

        return $response->withHeader('Clear-Site-Data', $value);
    }
}
