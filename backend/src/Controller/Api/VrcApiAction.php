<?php

namespace App\Controller\Api;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\VrcApi;
use Psr\Http\Message\ResponseInterface;

final readonly class VrcApiAction
{
    public function __construct(
        private VrcApi $vrcApi
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $postdata = $request->getBody()->getContents();
        $request = json_decode($postdata, true);

        if ($request['type'] == 'notification') {
            $notification = $request['content'];
            $notification_type = $notification['type'];

            if ($notification_type == 'friendRequest') {
                $notification_id = $notification['id'];

                $this->vrcApi->sendRequest(
                    method: 'PUT',
                    path: "api/1/auth/user/notifications/$notification_id/accept",
                    priority: true,
                    async: true
                );
            }
        }

        return $response->withStatus(200);
    }
}
