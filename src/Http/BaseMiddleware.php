<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https//:github.com/adhocore>
 *
 * Licensed under MIT license.
 */

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
     * Abort with response.
     *
     * @param int   $status
     * @param mixed $content If not string, will be json encoded
     * @param array $headers
     *
     * @return bool Always false
     */
    protected function abort(int $status, $content = null, array $headers = []): bool
    {
        $this->stop();

        $response = $this->di('response');

        foreach ($headers as $key => $value) {
            $response->setHeader($key, $value);
        }

        if ($content && !\is_scalar($content)) {
            $response->setJsonContent($content);
        } else {
            $response->setContent($content);
        }

        $response->setStatusCode($status)->send();

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
        $route  = $router->getMatchedRoute();
        $name   = $route ? $route->getName() : null;

        return [$name, $router->getRewriteUri()];
    }
}
