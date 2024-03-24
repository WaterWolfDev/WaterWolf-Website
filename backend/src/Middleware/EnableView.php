<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\ServerRequest;
use App\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Enable the View template renderer.
 */
final class EnableView implements MiddlewareInterface
{
    public function __construct(
        private readonly View $view
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $view = $this->view->setRequest($request);

        $request = $request->withAttribute(
            ServerRequest::ATTR_VIEW,
            $view
        );

        return $handler->handle($request);
    }
}
