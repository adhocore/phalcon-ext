<?php

namespace PhalconExt\Http;

use PhalconExt\Di\ProvidesDi;
use Phalcon\Events\Event;
use Phalcon\Mvc\DispatcherInterface as Dispatcher;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Micro as MicroApplication;
use Phalcon\Mvc\Micro\MiddlewareInterface;

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
     * Common handler for both micro and mvc app.
     *
     * @return bool
     */
    abstract protected function handle(): bool;

    public function beforeExecuteRoute(Event $event, Dispatcher $dispatcher, $data = null): bool
    {
        return $this->handle();
    }

    /**
     * @param MicroApplication $app
     *
     * @return bool
     */
    public function call(MicroApplication $app): bool
    {
        return $this->handle();
    }

    protected function disableView()
    {
        if ($this->di('view') instanceof View) {
            $this->di('view')->disable();
        }
    }

    /**
     * Abort with failure response.
     *
     * @param  int  $status
     *
     * @return bool
     */
    protected function abort(int $status): bool
    {
        $this->di('response')->setContent('')->setStatusCode($status)->send();

        return false;
    }
}
