<?php

namespace PhalconExt\Http;

use Phalcon\Http\Request;
use Phalcon\Http\Response;
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
    protected $config = [];

    /** @var string */
    protected $configKey;

    public function __construct()
    {
        $this->config = $this->di('config')->toArray()[$this->configKey];
    }

    /**
     * Sets itself to be triggered on before &/or after route execution events.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->isMicro()) {
            $this->di('application')->before($this);
            $this->di('application')->after([$this, 'callAfter']);

            return;
        }

        $evm = $this->di('eventsManager');

        $evm->attach('dispatch:beforeExecuteRoute', $this);
        $evm->attach('dispatch:afterExecuteRoute', $this);

        $this->di('dispatcher')->setEventsManager($evm);
    }

    /**
     * Before route handler for both micro and mvc app.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return bool
     */
    abstract public function before(Request $request, Response $response): bool;

    /**
     * After route handler for both micro and mvc app.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return bool
     */
    public function after(Request $request, Response $response): bool
    {
        // Implementing class may extend this and do the needful.
        return true;
    }

    /**
     * Before route executed in mvc.
     *
     * @return bool
     */
    public function beforeExecuteRoute(): bool
    {
        return $this->before($this->di('request'), $this->di('response'));
    }

    /**
     * After route executed in mvc.
     *
     *@return bool
     */
    public function afterExecuteRoute(): bool
    {
        return $this->after($this->di('request'), $this->di('response'));
    }

    /**
     * Before handler for micro app.
     *
     * @param MicroApplication $app
     *
     * @return bool
     */
    public function call(MicroApplication $app): bool
    {
        return $this->before($this->di('request'), $this->di('response'));
    }

    /**
     * After handler for micro app.
     *
     * @return bool
     */
    public function callAfter(): bool
    {
        return $this->after($this->di('request'), $this->di('response'));
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
    protected function getRouteNameUri(): array
    {
        $router = $this->di('router');
        $route  = $router->getMatchedRoute();
        $name   = $route ? $route->getName() : null;

        return [$name, $router->getRewriteUri()];
    }
}
