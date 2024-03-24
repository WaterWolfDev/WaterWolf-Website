<?php

declare(strict_types=1);

namespace App\Middleware\Auth;

use App\Entity\User;
use App\Exception\NotLoggedInException;
use App\Exception\PermissionDeniedException;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequireAdmin implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        /** @var User|null $user */
        $user = $request->getAttribute(ServerRequest::ATTR_CURRENT_USER);

        if ($user === null) {
            throw new NotLoggedInException($request);
        }

        if (!$user->isAdmin()) {
            throw new PermissionDeniedException($request);
        }

        return $handler->handle($request);
    }
}
