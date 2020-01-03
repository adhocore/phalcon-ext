<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Http\Middleware;

use Phalcon\Http\Request;
use Phalcon\Http\Response;
use PhalconExt\Http\BaseMiddleware;

/**
 * Cors middleware with preflight.
 *
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 */
class Cors extends BaseMiddleware
{
    /** @var string */
    protected $origin;

    protected $configKey = 'cors';

    /**
     * Handle the cors.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return bool
     */
    public function before(Request $request, Response $response): bool
    {
        $this->origin = $request->getHeader('Origin');

        if (!$this->isApplicable($request)) {
            return true;
        }

        if ($this->canPreflight($request)) {
            return $this->preflight($request, $response);
        }

        return $this->serve($response);
    }

    /**
     * If cors is applicable for this request.
     *
     * Not applicable if origin is empty or same as current host.
     *
     * @param Request $request
     *
     * @return bool
     */
    protected function isApplicable(Request $request): bool
    {
        if (empty($this->origin)) {
            return false;
        }

        return $this->origin !== $request->getScheme() . '://' . $request->getHttpHost();
    }

    /**
     * Check if request can be served as preflight.
     *
     * @param Request $request
     *
     * @return bool
     */
    protected function canPreflight(Request $request): bool
    {
        if (empty($request->getHeader('Access-Control-Request-Method')) ||
            $request->getMethod() !== 'OPTIONS'
        ) {
            return false;
        }

        return $this->isOriginAllowed();
    }

    /**
     * Handle preflight.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return bool
     */
    protected function preflight(Request $request, Response $response): bool
    {
        if (!\in_array($request->getHeader('Access-Control-Request-Method'), $this->config['allowedMethods'])) {
            return $this->abort(405, 'Request method not allowed.');
        }

        if (!$this->areHeadersAllowed($request->getHeader('Access-Control-Request-Headers'))) {
            return $this->abort(403, 'Request header not allowed');
        }

        return $this->abort(200, null, [
            'Access-Control-Allow-Origin'      => $this->origin,
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods'     => \implode(',', $this->config['allowedMethods']),
            'Access-Control-Allow-Headers'     => \implode(',', $this->config['allowedHeaders']),
            'Access-Control-Max-Age'           => $this->config['maxAge'],
        ]);
    }

    /**
     * Check if cors headers from client are allowed.
     *
     * @param string|null $corsRequestHeaders
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

        return empty(\array_diff($corsRequestHeaders, $this->config['allowedHeaders']));
    }

    /**
     * Serve cors headers.
     *
     * @param Response $response
     *
     * @return bool
     */
    public function serve(Response $response): bool
    {
        if (!$this->isOriginAllowed()) {
            return $this->abort(403, 'Forbidden Origin');
        }

        $response
            ->setHeader('Access-Control-Allow-Origin', $this->origin)
            ->setHeader('Access-Control-Allow-Credentials', 'true');

        // Optionally set expose headers.
        if ($this->config['exposedHeaders'] ?? null) {
            $response->setHeader('Access-Control-Expose-Headers', \implode(', ', $this->config['exposedHeaders']));
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
}
