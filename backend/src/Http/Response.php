<?php

declare(strict_types=1);

namespace App\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class Response extends \Slim\Http\Response
{
    /**
     * Don't escape forward slashes by default on JSON responses.
     *
     * @param mixed $data
     * @param int|null $status
     * @param int $options
     * @param int $depth
     */
    public function withJson($data, ?int $status = null, int $options = 0, int $depth = 512): ResponseInterface
    {
        $options |= JSON_UNESCAPED_SLASHES;
        $options |= JSON_UNESCAPED_UNICODE;

        return parent::withJson($data, $status, $options, $depth);
    }
}
