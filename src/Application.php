<?php

// phpcs:disable PSR1.Classes
declare(strict_types=1);

use Aikeedo\Integrity\SystemIntegrityManager;
use Easy\Container\Container;
use Easy\Container\Exceptions\NotFoundException;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shared\Infrastructure\BootstrapperInterface;
use Shared\Infrastructure\ServiceProviderInterface;

class Application
{
    /** @var array<ServiceProviderInterface|string> */
    private array $providers = [];

    /** @var array<BootstrapperInterface|string> */
    private array $bootstrappers = [];

    private static ContainerInterface $staticContainer;

    /**
     * @template T
     * @param string|class-string<T> $id
     * @return ($id is class-string<T> ? T : mixed)
     */
    public static function make(string $id, mixed $default = null): mixed
    {
        try {
            return self::$staticContainer->get($id);
        } catch (NotFoundExceptionInterface $th) {
            //throw $th;
        }

        return $default;
    }

    /**
     * @param Container $container
     * @return void
     */
    public function __construct(
        private Container $container,
        private bool $isDebugModeEnabled = false,
    ) {
        $this->configErrorReporting();
        $this->setDefaultTimezone();
        $this->container->set(Application::class, $this);

        self::$staticContainer = $container;
    }

    /**
     * @param (ServiceProviderInterface|string)[] ...$providers
     * @return Application
     */
    public function addServiceProvider(
        ServiceProviderInterface|string ...$providers
    ): self {
        $this->providers = array_merge($this->providers, $providers);
        return $this;
    }

    /**
     * @param (BootstrapperInterface|string)[] ...$bootstrappers
     * @return Application
     */
    public function addBootstrapper(
        BootstrapperInterface|string ...$bootstrappers
    ): self {
        $this->bootstrappers = array_merge($this->bootstrappers, $bootstrappers);
        return $this;
    }

    /**
     * @return void
     * @throws NotFoundException
     * @throws Throwable
     * @throws Exception
     */
    public function boot(): void
    {
        $this->invokeServiceProviders();
        $this->invokeBootstrappers();

        try {
            $this->integrity();
        } catch (Throwable) {
        }
    }

    /**
     * This is a mirror of Container::set(). The purpose of this method is to
     * decouple the ServiceProviderInterface implementation from the
     * ContainerInterface implementation.
     *
     * @param string $abstract
     * @param mixed $concrete
     * @return Application
     */
    public function set(
        string $abstract,
        mixed $concrete = null
    ): self {
        $this->container->set($abstract, $concrete);
        return $this;
    }

    /**
     * @return void
     * @throws NotFoundException
     * @throws Throwable
     * @throws Exception
     */
    private function invokeServiceProviders(): void
    {
        foreach ($this->providers as $provider) {
            if (is_string($provider)) {
                $provider = $this->container->get($provider);
            }

            if (!($provider instanceof ServiceProviderInterface)) {
                throw new \Exception(sprintf(
                    "%s must implement %s",
                    get_class($provider),
                    ServiceProviderInterface::class
                ));
            }

            $provider->register($this);
        }
    }

    /**
     * @return void
     * @throws NotFoundException
     * @throws Throwable
     * @throws Exception
     */
    private function invokeBootstrappers(): void
    {
        foreach ($this->bootstrappers as $bootstrapper) {
            if (is_string($bootstrapper)) {
                $bootstrapper = $this->container->get($bootstrapper);
            }

            if (!($bootstrapper instanceof BootstrapperInterface)) {
                throw new \Exception(sprintf(
                    "%s must implement %s",
                    get_class($bootstrapper),
                    BootstrapperInterface::class
                ));
            }

            $bootstrapper->bootstrap();
        }
    }

    private function integrity(): void
    {
        if (PHP_SAPI === 'cli' || env('ENVIRONMENT', 'install') === 'install') {
            return;
        }

        $iam = $this->container->get(SystemIntegrityManager::class);
        $iam->audit(
            ServerRequestFactory::fromGlobals()->getUri()->getHost(),
            $this->container->get('version'),
            $this->container->get('license')
        );
    }

    /**
     * Configure error reporting
     * @return void
     */
    private function configErrorReporting(): void
    {
        // Report all errors
        error_reporting(E_ALL);

        // Display error only if debug mode is enabled
        ini_set('display_errors', $this->isDebugModeEnabled);

        set_error_handler($this->warningHandler(...), E_WARNING);
    }

    /**
     * @param int $errno
     * @param string $errstr
     * @param null|string $errfile
     * @param null|int $errline
     * @return false
     * @throws ErrorException
     */
    private function warningHandler(
        int $errno,
        string $errstr,
        ?string $errfile = null,
        ?int $errline = null
    ) {
        if (!(error_reporting() & $errno)) {
            // This error code is not included in error_reporting, so let it fall
            // through to the standard PHP error handler
            return false;
        }

        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /** @return void  */
    private function setDefaultTimezone(): void
    {
        date_default_timezone_set('UTC');
    }
}
