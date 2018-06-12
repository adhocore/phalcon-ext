<?php

namespace PhalconExt\Http;

use Phalcon\Mvc\DispatcherInterface as Dispatcher;
use Phalcon\Mvc\Micro as MicroApplication;
use Phalcon\Mvc\Micro\MiddlewareInterface;
use Phalcon\Mvc\View;
use PhalconExt\Di\ProvidesDi;

/**
 * A handy base for middlewares.
 *
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 */
abstract class BaseMiddleware implements MiddlewareInterface
{
    use ProvidesDi;

    /** @var array */
    protected $config;

    /** @var string */
    protected $configKey;

    public function __construct()
    {
        $this->config = $this->di('config')->toArray()[$this->configKey];
    }

    /**
     * Sets itself to be triggered on `beforeExecuteRoute` (or `before` in micro) events.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->isMicro()) {
            $this->di('application')->before($this);

            return;
        }

        $evm = $this->di('eventsManager');

        $evm->attach('dispatch:beforeExecuteRoute', $this);

        $this->di('dispatcher')->setEventsManager($evm);
    }

    /**
     * Common handler for both micro and mvc app.
     *
     * @return bool
     */
    abstract protected function handle(): bool;

    /**
     * Handler for mvc app.
     *
     * @return bool
     */
    public function beforeExecuteRoute(): bool
    {
        return $this->handle();
    }

    /**
     * Handler for micro app.
     *
     * @param MicroApplication $app
     *
     * @return bool
     */
    public function call(MicroApplication $app): bool
    {
        return $this->handle();
    }

    /**
     * Disable view if possible.
     *
     * @return void
     */
    protected function disableView()
    {
        if ($this->di('view') instanceof View) {
            $this->di('view')->disable();
        }
    }

    /**
     * Abort with failure response.
     *
     * @param int $status
     *
     * @return bool
     */
    protected function abort(int $status): bool
    {
        $this->di('response')->setContent('')->setStatusCode($status)->send();

        return false;
    }

    /**
     * Checks if current app is micro.
     *
     * @return bool
     */
    protected function isMicro(): bool
    {
        static $isMicro = null;

        if (null !== $isMicro) {
            return $isMicro;
        }

        if (!$this->di()->has('application')) {
            return $isMicro = false;
        }

        return $isMicro = $this->di('application') instanceof MicroApplication;
    }

    /**
     * Get routeName and Url tuple.
     *
     * @return array [name, 'uri']
     */
    protected function getRouteNameUrl(): array
    {
        $router = $this->di('router');
        $route  = $router->getMatchedRoute();
        $name   = $route ? $route->getName() : null;

        return [$name, $router->getRewriteUri()];
    }
}
