<?php

declare(strict_types=1);

namespace Presentation\Middlewares;

use Easy\Container\Attributes\Inject;
use Option\Infrastructure\OptionResolver;
use Presentation\RequestHandlers\Admin\AbstractAdminRequestHandler;
use Presentation\RequestHandlers\App\AppView;
use Presentation\Resources\Api\UserResource;
use Presentation\Resources\Api\WorkspaceResource;
use Presentation\Resources\CurrencyResource;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\Config\Config;
use Shared\Infrastructure\Navigation\Registry;
use Twig\Environment;
use User\Domain\Entities\UserEntity;

class ViewMiddleware implements MiddlewareInterface
{
    /**
     * @param Environment $twig 
     * @param StreamFactoryInterface $streamFactory 
     * @return void 
     */
    public function __construct(
        private Environment $twig,
        private StreamFactoryInterface $streamFactory,
        private OptionResolver $optionResolver,
        private Registry $navigation,
        private Config $config,

        #[Inject('version')]
        private string $version,

        #[Inject('license')]
        private string $license,

        #[Inject('config.locale.locales')]
        private array $locales = [],

        #[Inject('option.theme')]
        private string $theme = 'heyaikeedo/default',
    ) {}

    /** @inheritDoc */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $resp = $handler->handle($request);

        if (!($resp instanceof ViewResponse)) {
            return $resp;
        }

        $data = $resp->getData();
        $data = array_merge($data, $this->optionResolver->getOptionMap());
        $data = array_merge($data, [
            'config' => $this->config->all(),
        ]);

        if (
            $this->optionResolver->canResolve('option.site.is_secure')
            && $this->optionResolver->canResolve('option.site.domain')
        ) {
            $data['option']['site']['url'] =
                ($this->optionResolver->resolve('option.site.is_secure') ? 'https' : 'http')
                . '://' . $this->optionResolver->resolve('option.site.domain');
        }

        $data['version'] = $this->version;
        $data['license'] = $this->license;
        $data['theme'] = $this->theme;
        $data['environment'] = env('ENVIRONMENT');
        $data['nav'] = $this->navigation;

        $viewNamespace = $this->getViewNamespace($request);
        $data['view_namespace'] = $viewNamespace;
        $data['currency'] = new CurrencyResource(
            $this->optionResolver->resolve('option.billing.currency') ?: 'USD'
        );

        $user = $request->getAttribute(UserEntity::class);

        if ($user) {
            /**
             * @deprecated
             * `auth_user` twig variable is deprecated. Use `user` instead.
             * Dont remove this variable until themese are updated.
             */
            $data['auth_user'] = new UserResource($user);
            $data['user'] = new UserResource($user, ['workspace']);
            $data['workspace'] = new WorkspaceResource($user->getCurrentWorkspace(), ['user']);
        }

        $locales = [];
        foreach ($this->locales as $locale) {
            // Add name to locale for backward compatibility
            $locales[] = [...$locale, 'name' => $locale['code']];
        }

        $data['locales'] = $locales;
        $data['locale'] = $request->getAttribute('theme.locale');
        if (in_array($viewNamespace, ['app', 'admin'])) {
            $data['locale'] = $request->getAttribute('locale');
        }

        /**
         * @deprecated
         * `theme_locale` twig variable is deprecated and kept for backward compatibility.
         * Dont remove this variable until themese are updated.
         */
        $data['theme_locale'] = $request->getAttribute('theme.locale') ? $request->getAttribute('theme.locale')['code'] : null;

        $colors = ['accent', 'accent_content'];
        foreach ($colors as $color) {
            if (
                !isset($data['option']['color_scheme'][$color])
                || !$data['option']['color_scheme'][$color]
            ) {
                // Remove
                unset($data['option']['color_scheme'][$color]);
                continue;
            }

            // Convert hex to rgb as assoc array
            $data['option']['color_scheme'][$color] = [
                'hex' => $data['option']['color_scheme'][$color],
                'r' => hexdec(substr($data['option']['color_scheme'][$color], 1, 2)),
                'g' => hexdec(substr($data['option']['color_scheme'][$color], 3, 2)),
                'b' => hexdec(substr($data['option']['color_scheme'][$color], 5, 2)),
                'rgb' => implode(" ", sscanf($data['option']['color_scheme'][$color], '#%02x%02x%02x')),
            ];
        }

        $stream = $this->streamFactory->createStream();
        $stream->write(
            $this->twig->render(
                $resp->getTemplate(),
                $data
            )
        );

        return $resp->withBody($stream);
    }

    /**
     * @param ServerRequestInterface $request 
     * @return null|string 
     */
    private function getViewNamespace(ServerRequestInterface $request): ?string
    {
        $handler = $request->getAttribute(RequestHandlerInterface::class);

        if ($handler instanceof AppView) {
            return 'app';
        }

        if ($handler instanceof AbstractAdminRequestHandler) {
            return 'admin';
        }

        return null;
    }
}
