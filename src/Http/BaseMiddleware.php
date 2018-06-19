<?php

namespace PhalconExt\Http;

use Phalcon\Http\Response;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Micro as MicroApplication;
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
abstract class BaseMiddleware
{
    use ProvidesDi;

    /** @var array */
    protected $config = [];

    /** @var string */
    protected $configKey;

    public function __construct()
    {
        $this->config = $this->di('config')->toArray()[$this->configKey];
    }

    /**
     * Stop the app execution and disable view if possible.
     */
    protected function stop()
    {
        if ($this->di('view') instanceof View) {
            $this->di('view')->disable();
        }

        if ($this->isMicro()) {
            $this->di('application')->stop();
        }
    }

    /**
     * Abort with failure response.
     *
     * @param int    $status
     * @param string $body
     *
     * @return bool
     */
    protected function abort(int $status, string $body = null): bool
    {
        $this->stop();

        $this->di('response')->setStatusCode($status)->setContent($body)->send();

        return false;
    }

    /**
     * Checks if current app is micro.
     *
     * @return bool
     */
    protected function isMicro(): bool
    {
        return $this->di('application') instanceof MicroApplication;
    }

    /**
     * Get routeName and Uri tuple.
     *
     * @return array ['name', 'uri']
     */
    protected function getRouteNameUri(): array
    {
        $router = $this->di('router');
        $route = $router->getMatchedRoute();
        $name = $route ? $route->getName() : null;

        return [$name, $router->getRewriteUri()];
    }
}
