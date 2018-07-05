<?php

namespace PhalconExt\Cli;

use Ahc\Cli\Helper\OutputHelper;
use Ahc\Cli\Input\Command;
use Ahc\Cli\Output\Writer;
use Phalcon\Cli\Console;

trait MiddlewareTrait
{
    protected $middlewares = [
        Middleware\Factory::class,
    ];

    protected function bindEvents(Console $console)
    {
        $evm = $this->di('eventsManager');

        $evm->attach('dispatch', $console);
        $console->setEventsManager($evm);

        $this->di('dispatcher')->setEventsManager($evm);
    }

    public function middleware(string $class): self
    {
        $this->middlewares[] = $class;

        return $this;
    }

    public function middlewares(): array
    {
        return $this->middlewares;
    }

    public function beforeExecuteRoute(): bool
    {
        return $this->relay('before');
    }

    public function afterExecuteRoute(): bool
    {
        return $this->relay('after');
    }

    protected function relay(string $event): bool
    {
        foreach ($this->middlewares as $middleware) {
            if (!$this->call($event, $middleware)) {
                return false;
            }
        }

        return true;
    }

    protected function call(string $event, $middleware): bool
    {
        if (\is_string($middleware)) {
            $middleware = $this->di($middleware);
        }

        if (!\method_exists($middleware, $event)) {
            return true;
        }

        return $middleware->$event($this->di('console'));
    }
}
