<?php

declare(strict_types=1);

namespace App;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Twig\EnvironmentAwareExtension;
use App\Twig\RequestAwareExtension;
use App\Twig\RouterAwareExtension;
use App\Twig\ViteManifestExtension;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\ContainerRuntimeLoader;

final class View
{
    private readonly TwigEnvironment $twig;

    public function __construct(
        ContainerRuntimeLoader $diLoader,
        RouterAwareExtension $routerAwareExtension,
        ViteManifestExtension $viteExtension,
        EnvironmentAwareExtension $envExtension
    ) {
        $loader = new FilesystemLoader(
            dirname(__DIR__) . '/templates'
        );

        $this->twig = new TwigEnvironment($loader, [
            'cache' => Environment::getTempDirectory() . '/templates',
            'debug' => Environment::isDev(),
        ]);

        $this->twig->addRuntimeLoader($diLoader);

        $this->twig->addExtension($routerAwareExtension);
        $this->twig->addExtension($viteExtension);
        $this->twig->addExtension($envExtension);
    }

    public function setRequest(ServerRequestInterface $request): self
    {
        assert($request instanceof ServerRequest);

        $this->twig->addExtension(new RequestAwareExtension($request));

        return $this;
    }

    public function render(string $name, array $data = []): string
    {
        $name = str_replace('.twig', '', $name) . '.twig';

        $template = $this->twig->load($name);
        return $template->render($data);
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
