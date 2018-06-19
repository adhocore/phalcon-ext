<?php

namespace PhalconExt\Http;

use Phalcon\Di\Injectable;
use Phalcon\Http\Request;
use Phalcon\Http\Response;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\DispatcherInterface as Dispatcher;
use Phalcon\Mvc\Micro as MicroApplication;
use PhalconExt\Di\ProvidesDi;

/**
 * A manager for middlewares.
 *
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 */
class Middlewares
{
    use ProvidesDi;

    protected $middlewares = [];

    public function __construct(array $middlewares)
    {
        $this->middlewares = $middlewares;
    }

    /**
     * Wraps app with middlewares and runs the app.
     *
     * @param Injectable $app The app instance: micro or mvc
     */
    public function wrap(Injectable $app)
    {
        $isMicro = $app instanceof MicroApplication;

        if (!$app instanceof Application && !$isMicro) {
            throw new \InvalidArgumentException('The app instance is not one of micro or mvc');
        }

        if (!$this->di()->has('application')) {
            $this->di()->setShared('application', $app);
        }

        $isMicro ? $this->handleMicro($app) : $this->handleMvc($app);
    }

    /**
     * Setup before/after handers for micro app and run the app.
     *
     * @param Injectable $app
     *
     * @return void
     */
    protected function handleMicro(Injectable $app)
    {
        $app
            ->before([$this, 'beforeHandleRequest'])
            ->after([$this, 'beforeSendResponse']);

        $this->handleApp($app);
    }

    /**
     * Setup event handlers for mvc app and run the app.
     *
     * @param Injectable $app
     *
     * @return void
     */
    protected function handleMvc(Injectable $app)
    {
        $evm = $this->di('eventsManager');

        $evm->attach('application', $this);
        $evm->attach('dispatch', $this);
        $app->setEventsManager($evm);

        $this->di('dispatcher')->setEventsManager($evm);

        $this->handleApp($app);
    }

    /**
     * Handles http requests to the app.
     *
     * @param Injectable $app
     *
     * @return mixed Whatever is given by $app->handle().
     */
    protected function handleApp(Injectable $app)
    {
        $return = $app->handle();

        $response = $this->di('response');

        if (!$response->isSent()) {
            $response->send();
        }

        return $return;
    }

    public function beforeHandleRequest(): bool
    {
        return $this->invoke('before');
    }

    public function beforeSendResponse(): bool
    {
        return $this->invoke('after');
    }

    /**
     * Sends events to all the middlewares. Aborts if one fails with falsy return value.
     *
     * @param string $event
     *
     * @return bool
     */
    protected function invoke(string $event): bool
    {
        $args = [$this->di('request'), $this->di('response')];

        foreach ($this->middlewares as $middleware) {
            if (\is_string($middleware)) {
                $middleware = $this->di($middleware);
            }

            if (!\method_exists($middleware, $event)) {
                continue;
            }

            if (!$middleware->$event(...$args)) {
                return false;
            }
        }

        return true;
    }
}
