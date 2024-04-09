<?php

namespace App\Twig;

use App\Http\ServerRequest;
use FastRoute\Route;
use Slim\Routing\RouteContext;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class RequestAwareExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly ServerRequest $request
    ) {
    }

    public function getGlobals(): array
    {
        /** @var Route $route */
        $route = $this->request->getAttribute(RouteContext::ROUTE);

        return [
            'request' => $this->request,
            'route' => $route,
            'session' => $this->request->getSession(),
            'csrf' => $this->request->getCsrf(),
            'flash' => $this->request->getFlash(),
            'user' => $this->request->getCurrentUser(),
            'is_logged_in' => $this->request->isLoggedIn(),
        ];
    }
}
