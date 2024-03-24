<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Remove trailing slash from all URLs when routing.
 */
final class RemoveSlashes implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $uri = $request->getUri();
        $path = $uri->getPath();

        if ($path !== '/') {
            if (str_ends_with($path, '/')) {
                while (str_ends_with($path, '/')) {
                    $path = substr($path, 0, -1);
                }
            }

            if (str_ends_with($path, '.php')) {
                $path = substr($path, 0, -4);
            }

            $uri = $uri->withPath($path);
            return $handler->handle(
                $request->withUri($uri)
            );
        }

        return $handler->handle($request);
    }
}
