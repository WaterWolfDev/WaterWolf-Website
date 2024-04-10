<?php

namespace App\Twig;

use App\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ViteManifestExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('renderAssets', [$this, 'renderAssets'], [
                'is_safe' => ['html'],
            ]),
        ];
    }

    public function renderAssets(string $component): string
    {
        $assetRoot = '/static/dist';

        if (Environment::isProduction()) {
            // Convert from the Vite manifest format into HTML header tags.
            $manifestFile = Environment::getBaseDirectory() . '/web/static/dist/.vite/manifest.json';

            $vueComponents = json_decode(
                file_get_contents($manifestFile),
                true,
                512,
                \JSON_THROW_ON_ERROR
            );

            $includes = [
                'js' => $assetRoot . '/' . $vueComponents[$component]['file'],
                'css' => [],
                'prefetch' => [],
            ];

            $visitedNodes = [];
            $fetchCss = function ($component) use (
                $vueComponents,
                $assetRoot,
                &$includes,
                &$fetchCss,
                &$visitedNodes
            ): void {
                if (!isset($vueComponents[$component]) || isset($visitedNodes[$component])) {
                    return;
                }

                $visitedNodes[$component] = true;

                $componentInfo = $vueComponents[$component];
                if (isset($componentInfo['css'])) {
                    foreach ($componentInfo['css'] as $css) {
                        $includes['css'][] = $assetRoot . '/' . $css;
                    }
                }

                if (isset($componentInfo['file'])) {
                    $fileUrl = $assetRoot . '/' . $componentInfo['file'];
                    if ($fileUrl !== $includes['js']) {
                        $includes['prefetch'][] = $fileUrl;
                    }
                }

                if (isset($componentInfo['imports'])) {
                    foreach ($componentInfo['imports'] as $import) {
                        $fetchCss($import);
                    }
                }
            };

            $fetchCss($component);
        } else {
            $includes = [
                'js' => $assetRoot . '/' . $component,
                'css' => [],
                'prefetch' => [],
            ];
        }

        $html = [
            <<<HTML
                <script type="module" src="{$includes['js']}"></script>
            HTML
            ,
        ];

        foreach ($includes['prefetch'] as $prefetchDep) {
            $html[] = <<<HTML
                <link rel="modulepreload" href="{$prefetchDep}" />
            HTML;
        }

        foreach ($includes['css'] as $cssDep) {
            $html[] = <<<HTML
                <link rel="stylesheet" href="{$cssDep}" />
            HTML;
        }

        return implode("\n", $html);
    }
}
