<?php

namespace App\Exception;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Throwable;

final class NotLoggedInException extends HttpException
{
    public function __construct(
        ServerRequestInterface $request,
        string $message = 'Please log in to access this page.',
        int $code = 403,
        ?Throwable $previous = null
    ) {
        parent::__construct($request, $message, $code, $previous);
    }
}
