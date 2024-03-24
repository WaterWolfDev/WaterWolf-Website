<?php

declare(strict_types=1);

namespace App\Middleware\Auth;

use App\Exception\NotLoggedInException;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequireLoggedIn implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $user = $request->getAttribute(ServerRequest::ATTR_CURRENT_USER);

        if ($user === null) {
            throw new NotLoggedInException($request);
        }

        return $handler->handle($request);
    }
}
