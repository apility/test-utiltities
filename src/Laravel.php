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
use Illuminate\Support\Facades\Config;

/**
 * Bootstraps a minimal Illuminate Container.
 * This allows you to test your service providers without
 * bootstrapping the full Laravel framework.
 */
class Laravel
{
    /** @var App */
    protected $app;

    /** @var string */
    protected $root;

    /** @var array */
    protected $earlyProviders = [];

    /** @var array */
    protected $providers = [];

    /** @var array */
    protected $config = [];

    /**
     * @param string|null $root
     * @param array $providers
     * @param array $config
     */
    protected function __construct()
    {
        $this->earlyProviders = [];
        $this->lateProviders = [];
        $this->config = [];
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
    public function withEarlyProvider(string $provider): self
    {
        $this->earlyProviders[] = $provider;
        return $this;
    }

    /**
     * @param array $providers
     * @return static
     */
    public function withEarlyProviders(array $providers): self
    {
        foreach ($providers as $provider) {
            $this->withEarlyProvider($provider);
        }

        return $this;
    }


    /**
     * @param string $provider
     * @return static
     */
    public function withProvider(string $provider): self
    {
        $this->lateProviders[] = $provider;
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
        if (!function_exists('env')) {
            /**
             * Emulates Laravels env method
             *
             * @param string $key
             * @return mixed
             */
            function env($key, $default = null)
            {
                return $_ENV[$key] ?? $default;
            }
        }

        // Load .env file
        $dotenv = Dotenv::createImmutable($path ?? $this->root);
        $dotenv->load();
    }

    /**
     * @return void
     */
    protected function bootstrapConfig()
    {
        if (!function_exists('config')) {
            /**
             * Emulates Laravels config method
             *
             * @param string $key
             * @return mixed
             */
            function config($key, $default = null)
            {
                return Config::get($key, $default);
            }
        }

        // This represents what would usually be the app configuration
        $this->app->singleton('config', fn () => new Repository($this->config));
    }

    /**
     * @return void
     */
    protected function bootstrapFacades()
    {
        // Set the facade application to make facades work
        Facade::setFacadeApplication($this->app);
    }

    /**
     * @param string $provider
     * @return ServiceProvider
     */
    protected function registerServiceProvider(string $provider): ServiceProvider
    {
        /** @var ServiceProvider $serviceProvider */
        $serviceProvider = new $provider($this->app);
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
            $this->app->call([$provider, 'boot']);
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
        $this->app = new Container;
        $this->app['app'] = $this->app;
        $this->app['files'] = new Filesystem;
        $this->app[ApplicationContract::class] = $this->app;

        $this->bootstrapFacades();
        $this->bootstrapConfig();
        $this->bootstrapServiceProviders($this->earlyProviders);
        $this->bootstrapServiceProviders($this->lateProviders);

        if ($callback) {
            return $callback($this->app);
        }

        return $this->app;
    }

    /**
     * @return static
     */
    public static function createApplication(): self
    {
        return new static();
    }
}
