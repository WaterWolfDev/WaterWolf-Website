<?php

namespace App\Twig;

use Slim\Interfaces\RouteParserInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

final class RouterAwareExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly RouteParserInterface $router
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'router' => $this->router,
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('urlFor', [$this->router, 'urlFor']),
        ];
    }
}
