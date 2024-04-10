<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Environment;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Apply the "X-Forwarded-Proto" header if it exists.
 */
class ApplyXForwardedProto implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if (Environment::isProduction()) {
            $uri = $request->getUri();
            if ($uri->getScheme() !== 'https') {
                $uri = $uri->withScheme('https');
                $request = $request->withUri($uri);
            }
        } elseif ($request->hasHeader('X-Forwarded-Proto')) {
            $uri = $request->getUri();
            $uri = $uri->withScheme($request->getHeaderLine('X-Forwarded-Proto'));
            $request = $request->withUri($uri);
        }

        return $handler->handle($request);
    }
}
