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
     *
     * @return mixed
     */
    public function wrap(Injectable $app)
    {
        if ($app instanceof MicroApplication) {
            return $this->handleMicro($app);
        }

        if (!$app instanceof Application) {
            throw new \InvalidArgumentException('The app instance is not one of micro or mvc');
        }

        $this->di()->setShared('application', $app);

        return $this->handleMvc($app);
    }

    /**
     * Setup before/after handers for micro app and run the app.
     *
     * @param Injectable $app
     *
     * @return mixed
     */
    protected function handleMicro(Injectable $app)
    {
        $app
            ->before(function () {
                return $this->relay('before');
            })
            ->after(function () {
                return $this->relay('after');
            });

        return $this->handleApp($app);
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

        return $this->handleApp($app);
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
        return $this->relay('before');
    }

    public function beforeSendResponse(): bool
    {
        return $this->relay('after');
    }

    /**
     * Sends events to all the middlewares. Aborts if one fails with falsy return value.
     *
     * @param string $event
     *
     * @return bool
     */
    protected function relay(string $event): bool
    {
        foreach ($this->middlewares as $middleware) {
            if (!$this->call($event, $middleware)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Call event method on middleware.
     *
     * @param string $event
     * @param mixed  $middleware
     *
     * @return bool
     */
    protected function call(string $event, $middleware): bool
    {
        if (\is_string($middleware)) {
            $middleware = $this->di($middleware);
        }

        if (!\method_exists($middleware, $event)) {
            return true;
        }

        return $middleware->$event($this->di('request'), $this->di('response'));
    }
}
