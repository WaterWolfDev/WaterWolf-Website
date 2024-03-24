<?php

namespace App\Exception;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Throwable;

final class NotFoundException extends HttpException
{
    public function __construct(
        ServerRequestInterface $request,
        string $message = 'Record not found!',
        int $code = 404,
        ?Throwable $previous = null
    ) {
        parent::__construct($request, $message, $code, $previous);
    }

    public static function user(ServerRequestInterface $request): self
    {
        return new self($request, 'User not found!');
    }

    public static function world(ServerRequestInterface $request): self
    {
        return new self($request, 'World not found!');
    }

    public static function poster(ServerRequestInterface $request): self
    {
        return new self($request, 'Poster not found!');
    }
}
