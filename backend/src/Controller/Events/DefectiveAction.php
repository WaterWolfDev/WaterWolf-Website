<?php

namespace App\Controller\Events;

use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final readonly class DefectiveAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $eventConfig = require(Environment::getBaseDirectory() . '/backend/config/event_defective.php');

        return $request->getView()->renderToResponse(
            $response,
            'events/defective',
            $eventConfig
        );
    }
}
