<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Twig;

use ReflectionClass;
use Shared\Infrastructure\Atributes\BuiltInAspect;
use Shared\Infrastructure\Helpers\AssetHelper;
use Throwable;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\TemplateWrapper;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class CustomExtension extends AbstractExtension implements ExtensionInterface
{
    public function __construct(
        private AssetHelper $assetHelper
    ) {}

    public function getFunctions()
    {
        $funcs = [];

        // Allowed PHP functions
        $funcs[] = new TwigFunction('hash_hmac', 'hash_hmac');

        // Custom functions
        $funcs[] = new TwigFunction(
            'template',
            $this->template(...),
            ['needs_environment' => true]
        );

        return $funcs;
    }

    public function getFilters()
    {
        $filters = [];

        $filters[] = new TwigFilter(
            'asset', // asset_url is already used for the theme
            $this->assetHelper->getAssetUrl(...)
        );

        return $filters;
    }

    public function getTests()
    {
        return [
            new TwigTest(
                'builtin',
                $this->isBuiltinAspect(...),
            ),
        ];
    }

    private function template(
        Environment $env,
        string $template,
        ?string $name = null,
        ?bool $strict = false
    ): TemplateWrapper {
        if ($strict) {
            return $env->createTemplate($template, $name);
        }

        try {
            $env->disableStrictVariables();
            $wrp = $env->createTemplate($template, $name);
            // $env->enableStrictVariables();

            return $wrp;
        } catch (Throwable $th) {
            return $env->createTemplate('{% verbatim %}' . $template . '{% endverbatim %}', $name);
        }
    }

    private function isBuiltinAspect($variable): bool
    {
        $rc = new ReflectionClass($variable);
        return count($rc->getAttributes(BuiltInAspect::class)) > 0;
    }
}
