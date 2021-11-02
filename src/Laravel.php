<?php

namespace Apility\Testing;

use Closure;

use Dotenv\Dotenv;

use Illuminate\Contracts\Container\Container as App;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

/**
 * Bootstraps a minimal Illuminate Container.
 * This allows you to test your service providers without
 * bootstrapping the full Laravel framework.
 */
class Laravel extends Container implements ApplicationContract
{
    /** @var string */
    protected $root;

    /** @var array */
    protected $frameworkProviders = [];

    /** @var array */
    protected $providers = [];

    /** @var array */
    protected $config = [];

    protected $booted = false;

    /**
     * @param string|null $root
     * @param array $providers
     * @param array $config
     */
    protected function __construct()
    {
        static::$instance = $this;
        $this->booted = false;
        $this->frameworkProviders = [];
        $this->providers = [];
        $this->config = [];
    }

    public function version()
    {
        return '8.x.x';
    }

    public function basePath($path = '')
    {
        return $this->root;
    }

    public function bootstrapPath($path = '')
    {
        return null;
    }

    public function configPath($path = '')
    {
        return null;
    }

    public function databasePath($path = '')
    {
        return null;
    }

    public function resourcePath($path = '')
    {
        return null;
    }

    public function storagePath()
    {
        return null;
    }

    public function environment(...$environments)
    {
        return null;
    }

    public function runningInConsole()
    {
        return false;
    }

    public function runningUnitTests()
    {
        return false;
    }

    public function isDownForMaintenance()
    {
        return false;
    }

    public function registerConfiguredProviders()
    {
    }

    public function register($provider, $force = false)
    {
        $this->withProvider($provider);
    }

    public function registerDeferredProvider($provider, $service = null)
    {
        $this->withProvider($provider);
    }

    public function resolveProvider($provider)
    {
        return $this->make($provider);
    }

    public function booting($callback)
    {
    }

    public function booted($callback)
    {
    }

    public function bootstrapWith(array $bootstrappers)
    {
    }

    public function getLocale()
    {
        return 'en';
    }

    public function getNamespace()
    {
        return 'App';
    }

    public function getProviders($provider)
    {
        return $this->make($provider);
    }

    public function hasBeenBootstrapped()
    {
        return $this->booted;
    }

    public function loadDeferredProviders()
    {
    }

    public function setLocale($locale)
    {
    }

    public function shouldSkipMiddleware()
    {
    }

    public function terminate()
    {
        die();
    }

    /**
     * @param string $root
     * @return static
     */
    public function withRoot(string $root): self
    {
        $this->root = $root;
        return $this;
    }

    /**
     * @param bool $withDotenv
     * @return static
     */
    public function withDotenv(?string $path = null): self
    {
        $this->bootstrapDotenv($path);
        return $this;
    }

    /**
     * @param string $provider
     * @return static
     */
    public function withFrameworkProvider(string $provider): self
    {
        $this->frameworkProviders[] = $provider;
        return $this;
    }

    /**
     * @param array $providers
     * @return static
     */
    public function withFrameworkProviders(array $providers): self
    {
        foreach ($providers as $provider) {
            $this->withFrameworkProvider($provider);
        }

        return $this;
    }


    /**
     * @param string $provider
     * @return static
     */
    public function withProvider(string $provider): self
    {
        $this->providers[] = $provider;
        return $this;
    }

    /**
     * @param array $providers
     * @return static
     */
    public function withProviders(array $providers): self
    {
        foreach ($providers as $provider) {
            $this->withProvider($provider);
        }

        return $this;
    }

    /**
     * @param array $config
     * @return static
     */
    public function withConfig(array $config): self
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @return void
     */
    protected function bootstrapDotenv(?string $path = null)
    {
        // Load .env file
        $dotenv = Dotenv::createImmutable($path ?? $this->root);
        $dotenv->load();
    }

    /**
     * @return void
     */
    protected function bootstrapConfig()
    {
        // This represents what would usually be the app configuration
        $this->singleton('config', fn () => new Repository($this->config));
    }

    /**
     * @return void
     */
    protected function bootstrapFacades()
    {
        // Set the facade application to make facades work
        Facade::setFacadeApplication($this);
    }

    /**
     * @param string $provider
     * @return ServiceProvider
     */
    protected function registerServiceProvider(string $provider): ServiceProvider
    {
        /** @var ServiceProvider $serviceProvider */
        $serviceProvider = new $provider($this);
        $serviceProvider->register();
        return $serviceProvider;
    }

    /**
     * @param ServiceProvider $provider
     * @return void
     */
    protected function bootServiceProvider(ServiceProvider $provider)
    {
        if (method_exists($provider, 'boot')) {
            $this->call([$provider, 'boot']);
        }

        $provider->callBootedCallbacks();
    }

    /**
     * @return void
     */
    protected function bootstrapServiceProviders(array $providers)
    {
        foreach ($providers as $key => $provider) {
            $providers[$key] = $this->registerServiceProvider($provider);
        }

        foreach ($providers as $provider) {
            $this->bootServiceProvider($provider);
        }
    }

    /**
     * @return App The booted 'Laravel' app instance
     */
    public function boot(?Closure $callback = null): App
    {
        // This represents what would usually be the full Laravel app instance
        $this['app'] = $this;
        $this['files'] = new Filesystem;
        $this[ApplicationContract::class] = $this;

        $this->bootstrapFacades();
        $this->bootstrapConfig();
        $this->bootstrapServiceProviders($this->frameworkProviders);
        $this->bootstrapServiceProviders($this->providers);

        $this->booted = true;

        if ($callback) {
            return $callback($this);
        }

        return $this;
    }

    /**
     * @return static
     */
    public static function createApplication(): self
    {
        return new static();
    }
}
