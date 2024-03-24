<?php

namespace App\Exception;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Throwable;

final class PermissionDeniedException extends HttpException
{
    public function __construct(
        ServerRequestInterface $request,
        string $message = 'You do not have permission to access this page.',
        int $code = 403,
        ?Throwable $previous = null
    ) {
        parent::__construct($request, $message, $code, $previous);
    }
}
