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
use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

/**
 * Bootstraps a minimal Illuminate Container.
 * This allows you to test your service providers without
 * bootstrapping the full Laravel framework.
 */
class Laravel
{
    protected $root;

    protected $providers;

    protected $config = [];

    protected $loadDotenv = false;

    /**
     * @param string|null $root
     * @param array $providers
     * @param array $config
     */
    protected function __construct($root = null, $providers = [], $config = [])
    {
        $this->root = $root;
        $this->providers = $providers;
        $this->config = $config;
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
    public function withDotenv(bool $withDotenv = true): self
    {
        $this->loadDotenv = $withDotenv;
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
     * @param string $provider
     * @return static
     */
    public function withProvider(string $provider): self
    {
        $this->providers[] = $provider;
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
    protected function bootstrapDotenv()
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
        $dotenv = Dotenv::createImmutable($this->root);
        $dotenv->load();
    }

    /**
     * @param App $app
     * @return void
     */
    function bootstrapConfig(App $app)
    {
        // This represents what would usually be the app configuration
        $app->singleton('config', fn () => new Repository($this->config));
    }

    /**
     * @param App $app
     * @return void
     */
    public function bootstrapFacades(App $app)
    {
        $app->bind('app', fn () => $app);

        // Set the facade application to make facades work
        Facade::setFacadeApplication($app);
    }

    /**
     * @param App $app
     * @return void
     */
    public function bootstrapServiceProviders(App $app)
    {
        $registeredProviders = [];

        foreach ($this->providers as $provider) {
            /** @var ServiceProvider $serviceProvider */
            $serviceProvider = new $provider($app);
            $serviceProvider->register();
            $registeredProviders[$provider] = $serviceProvider;
        }

        foreach ($registeredProviders as $provider => $instance) {
            if (method_exists($instance, 'boot')) {
                $app->call([$instance, 'boot']);
            }

            $instance->callBootedCallbacks();
        }
    }

    /**
     * @return App The booted 'Laravel' app instance
     */
    public function run(?Closure $callback = null): App
    {
        if ($this->loadDotenv) {
            $this->bootstrapDotenv();
        }

        // This represents what would usually be the full Laravel app instance
        $app = new Container;

        $app['app'] = $app;
        $app['files'] = new Filesystem;

        $app[ApplicationContract::class] = $app;

        $this->bootstrapFacades($app);
        $this->bootstrapConfig($app);
        $this->bootstrapServiceProviders($app);

        if ($callback) {
            return $callback($app);
        }

        return $app;
    }

    /**
     * @param string $root directory
     * @param array $providers Service providers to register.
     * @param array $config App configuration
     * @return static
     */
    public static function make($root = null, $providers = [], $config = []): self
    {
        return new static($root, $providers, $config);
    }
}
