<?php

declare(strict_types=1);

namespace App;

use App\Http\Response;
use App\Http\ServerRequest;
use App\View\GlobalSections;
use League\Plates\Engine;
use League\Plates\Template\Data;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Routing\RouteContext;

final class View extends Engine
{
    private GlobalSections $sections;

    public function __construct(
        Environment $environment,
        RouteParserInterface $router
    ) {
        parent::__construct(
            dirname(__DIR__) . '/templates',
            'phtml'
        );

        $this->sections = new GlobalSections();

        $this->addFolder('layouts', dirname(__DIR__) . '/templates/layouts');

        $this->addData(
            [
                'sections' => $this->sections,
                'environment' => $environment,
                'router' => $router,
            ]
        );
    }

    public function setRequest(ServerRequestInterface $request): self
    {
        $view = clone $this;

        $view->addData([
            'request' => $request,
        ]);

        if ($request instanceof ServerRequest) {
            $view->addData([
                'route' => $request->getAttribute(RouteContext::ROUTE),
                'session' => $request->getAttribute(ServerRequest::ATTR_SESSION),
                'csrf' => $request->getAttribute(ServerRequest::ATTR_SESSION_CSRF),
                'flash' => $request->getAttribute(ServerRequest::ATTR_SESSION_FLASH),
                'user' => $request->getCurrentUser(),
                'is_logged_in' => $request->isLoggedIn(),
            ]);
        }

        return $view;
    }

    public function reset(): void
    {
        $this->sections = new GlobalSections();
        $this->data = new Data();
    }

    /**
     * @param string $name
     * @param array $data
     */
    public function fetch(string $name, array $data = []): string
    {
        return $this->render($name, $data);
    }

    /**
     * Trigger rendering of template and write it directly to the PSR-7 compatible Response object.
     *
     * @param ResponseInterface $response
     * @param string $templateName
     * @param array $templateArgs
     */
    public function renderToResponse(
        ResponseInterface $response,
        string $templateName,
        array $templateArgs = []
    ): ResponseInterface {
        $response->getBody()->write(
            $this->render($templateName, $templateArgs)
        );
        return $response->withHeader('Content-type', 'text/html; charset=utf-8');
    }

    public static function staticPage(
        string $templateName,
        array $templateArgs = []
    ): callable {
        return function (
            ServerRequest $request,
            Response $response
        ) use (
            $templateName,
            $templateArgs
        ): ResponseInterface {
            return $request->getView()->renderToResponse(
                $response,
                $templateName,
                $templateArgs
            );
        };
    }
}
