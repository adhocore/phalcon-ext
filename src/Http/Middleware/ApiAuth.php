<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https//:github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Http\Middleware;

use Phalcon\Http\Request;
use Phalcon\Http\Response;
use PhalconExt\Contract\ApiAuthenticator;
use PhalconExt\Factory\ApiAuthenticator as FactoryAuthenticator;
use PhalconExt\Http\BaseMiddleware;

/**
 * Check authentication &/or authorization for api requests.
 *
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 */
class ApiAuth extends BaseMiddleware
{
    protected $configKey = 'apiAuth';

    /** @var ApiAuthenticator */
    protected $authenticator;

    public function __construct(ApiAuthenticator $authenticator = null)
    {
        parent::__construct();

        $this->authenticator = $authenticator ?? $this->di(FactoryAuthenticator::class);

        if (!$this->di()->has('authenticator')) {
            $this->di()->setShared('authenticator', $this->authenticator);
        }

        $this->authenticator->configure($this->config);
    }

    /**
     * Handle authentication.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return bool
     */
    public function before(Request $request, Response $response): bool
    {
        list($routeName, $currentUri) = $this->getRouteNameUri();

        if ($this->shouldGenerate($request, $currentUri)) {
            return $this->generateToken($request);
        }

        $requiredScope = $this->config['scopes'][$routeName] ?? $this->config['scopes'][$currentUri] ?? null;

        return $this->validateScope($request, $requiredScope);
    }

    protected function shouldGenerate(Request $request, string $currentUri): bool
    {
        return $request->isPost() && $this->config['authUri'] === $currentUri;
    }

    protected function generateToken(Request $request)
    {
        $payload = $request->getJsonRawBody(true) ?: $request->getPost();

        if (!$this->validateGenerate($payload)) {
            return $this->abort(401, 'Credentials missing');
        }

        if (!$this->authenticate($payload)) {
            return $this->abort(401, 'Credentials invalid');
        }

        return $this->serve($payload['grant_type']);
    }

    protected function validateGenerate(array $payload): bool
    {
        if (!isset($payload['grant_type'], $payload[$payload['grant_type']])) {
            return false;
        }

        if (!\in_array($payload['grant_type'], ['password', 'refresh_token'])) {
            return false;
        }

        return 'password' !== $payload['grant_type'] || isset($payload['username']);
    }

    protected function authenticate(array $payload): bool
    {
        if ('refresh_token' === $payload['grant_type']) {
            return $this->authenticator->byRefreshToken($payload['refresh_token']);
        }

        return $this->authenticator->byCredential($payload['username'], $payload['password']);
    }

    protected function serve(string $grantType): bool
    {
        $token = [
            'access_token' => $this->createJWT(),
            'token_type'   => 'bearer',
            'expires_in'   => $this->config['jwt']['maxAge'],
            'grant_type'   => $grantType,
        ];

        if ($grantType === 'password') {
            $token['refresh_token'] = $this->authenticator->createRefreshToken();
        }

        return $this->abort(200, $token);
    }

    protected function createJWT(): string
    {
        $jwt = [
            'sub'    => $this->authenticator->getSubject(),
            'scopes' => $this->authenticator->getScopes(),
        ];

        if ($this->config['jwt']['issuer'] ?? null) {
            $jwt['iss'] = $this->config['jwt']['issuer'];
        }

        // 'exp' is automatically added based on `config->apiAuth->jwt->maxAge`!
        return $this->di('jwt')->encode($jwt);
    }

    protected function validateScope(Request $request, string $requiredScope = null): bool
    {
        $claimedScopes = [];
        $msg           = 'Permission denied';
        $jwt           = $request->getHeader('Authorization');

        try {
            $claimedScopes = $this->getClaimedScopes($jwt);
        } catch (\InvalidArgumentException $e) {
            $msg = $e->getMessage();
        }

        if ($requiredScope && !\in_array($requiredScope, $claimedScopes)) {
            return $this->abort(403, $msg);
        }

        return true;
    }

    protected function getClaimedScopes(string $jwt = null): array
    {
        if ('' === $jwt = \str_replace('Bearer ', '', \trim($jwt))) {
            return [];
        }

        $decoded = $this->di('jwt')->decode($jwt);

        if ($decoded['sub'] ?? null) {
            $this->authenticator->bySubject($decoded['sub']);
        }

        return $decoded['scopes'] ?? [];
    }
}
