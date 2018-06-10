<?php

namespace PhalconExt\Http\Middleware;

use PhalconExt\Di\ProvidesDi;
use Phalcon\Events\Event;
use Phalcon\Mvc\DispatcherInterface;
use Phalcon\Mvc\Micro as MicroApplication;
use Phalcon\Mvc\Micro\MiddlewareInterface;

class Cors implements MiddlewareInterface
{
    use ProvidesDi;

    /** @var string */
    protected $origin;

    /** @var array Cors settings */
    protected $config;

    public function beforeExecuteRoute(Event $event, DispatcherInterface $dispatcher, $data = null)
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

    /**
     * Common handler for both micro and mvc app.
     *
     * @return bool
     */
    protected function handle(): bool
    {
        $this->origin = $this->di('request')->getHeader('Origin');
        $this->config = $this->di('config')->toArray()['cors'];

        if (!$this->isApplicable()) {
            return true;
        }

        if ($this->canPreflight()) {
            return $this->preflight();
        }

        return $this->serve();
    }

    /**
     * If cors is applicable for this request.
     *
     * Not applicable if origin is empty or same as current host.
     *
     * @return bool
     */
    protected function isApplicable(): bool
    {
        if (empty($this->origin)) {
            return false;
        }

        $request = $this->di('request');

        if ($this->origin === $request->getScheme() . '://' . $request->getHttpHost()) {
            return false;
        }

        return true;
    }

    /**
     * Check if request can be served as preflight.
     *
     * @return bool
     */
    protected function canPreflight() : bool
    {
        $request = $this->di('request');

        if (empty($request->getHeader('Access-Control-Request-Method')) ||
            $request->getMethod() !== 'OPTIONS'
        ) {
            return false;
        }

        return true;
    }

    /**
     * If origin is white listed.
     *
     * @return bool
     */
    protected function isOriginAllowed(): bool
    {
        if (\in_array('*', $this->config['allowedOrigins'])) {
            return true;
        }

        return \in_array($this->origin, $this->config['allowedOrigins']);
    }

    /**
     * Handle preflight.
     *
     * @return bool
     */
    protected function preflight(): bool
    {
        $request = $this->di('request');

        if (!\in_array($request->getHeader('Access-Control-Request-Method'), $this->config['allowedMethods'])) {
            return $this->abort(405);
        }

        if (!$this->areHeadersAllowed($request->getHeader('Access-Control-Request-Headers'))) {
            return $this->abort(403);
        }

        $this->di('view')->disable();

        $this->di('response')
            ->setHeader('Access-Control-Allow-Origin', $this->origin)
            ->setHeader('Access-Control-Allow-Credentials', 'true')
            ->setHeader('Access-Control-Allow-Methods', implode(', ', $this->config['allowedMethods']))
            ->setHeader('Access-Control-Allow-Headers', implode(', ', $this->config['allowedHeaders']))
            ->setHeader('Access-Control-Max-Age', $this->config['maxAge'])
            ->setContent('')
            ->send()
        ;

        return false;
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
        $this->di('response')->setStatusCode($status)->sendHeaders();

        return false;
    }

    /**
     * Check if cors headers from client are allowed.
     *
     * @param  $corsRequestHeaders string
     *
     * @return bool
     */
    protected function areHeadersAllowed(string $corsRequestHeaders = null)
    {
        if ('' === \trim($corsRequestHeaders)) {
            return true;
        }

        // Normalize request headers for comparison.
        $corsRequestHeaders = \array_map(
            'strtolower',
            \explode(',', \str_replace(' ', '', $corsRequestHeaders))
        );

        return \count(\array_diff($corsRequestHeaders, $this->config['allowedHeaders'])) > 0;
    }

    /**
     * Serve cors headers.
     *
     * @return bool
     */
    public function serve()
    {
        if (!$this->isOriginAllowed()) {
            return $this->abort(403);
        }

        $response = $this->di('response');

        $response
            ->setHeader('Access-Control-Allow-Origin', $this->origin)
            ->setHeader('Access-Control-Allow-Credentials', 'true')
        ;

        // Optionally set expose headers.
        if ($this->config['exposedHeaders'] ?? null) {
            $response->setHeader('Access-Control-Expose-Headers', \implode(', ', $this->config['exposedHeaders']));
        }

        return true;
    }
}
