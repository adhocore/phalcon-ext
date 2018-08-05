<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Test\Http\Middleware;

use PhalconExt\Http\Middleware\ApiAuth;
use PhalconExt\Test\WebTestCase;

class ApiAuthTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->middlewares = [ApiAuth::class];
    }

    public function test_auth_generate_401()
    {
        $this->doRequest('POST /api/auth', ['grant_type' => 'invalid', 'invalid' => 1])
            ->assertResponseNotOk()
            ->assertStatusCode(401)
            ->assertResponseContains('Credentials missing');

        $credentials = ['grant_type' => 'password'];

        $this->doRequest('POST /api/auth', $credentials)
            ->assertResponseNotOk()
            ->assertStatusCode(401)
            ->assertResponseContains('Credentials missing');

        $credentials += ['username' => 'adhocore', 'password' => 123456];

        $this->doRequest('POST /api/auth', $credentials)
            ->assertResponseNotOk()
            ->assertStatusCode(401)
            ->assertResponseContains('Credentials invalid');
    }

    public function test_auth_generate_200()
    {
        $credentials = ['grant_type' => 'password', 'username' => 'adhocore', 'password' => 123456];

        $this->ensureUser($credentials + ['scopes' => 'user']);

        $this->doRequest('POST /api/auth', $credentials)
            ->assertResponseOk()
            ->assertStatusCode(200)
            ->assertResponseKeys(['access_token', 'refresh_token'])
            ->assertResponseKey('grant_type', 'password')
            ->assertResponseKey('expires_in', $this->config('apiAuth.jwt.maxAge'));

        return $this->getJson();
    }

    public function test_refresh_401()
    {
        $credentials = ['grant_type' => 'refresh_token'];

        $this->doRequest('POST /api/auth', $credentials)
            ->assertResponseNotOk()
            ->assertStatusCode(401)
            ->assertResponseContains('Credentials missing');

        $credentials += ['refresh_token' => 'invalid'];

        $this->doRequest('POST /api/auth', $credentials)
            ->assertResponseNotOk()
            ->assertStatusCode(401)
            ->assertResponseContains('Credentials invalid');
    }

    /** @depends test_auth_generate_200 */
    public function test_refresh_200($token)
    {
        $credentials = ['grant_type' => 'refresh_token', 'refresh_token' => $token['refresh_token']];

        $this->doRequest('POST /api/auth', $credentials)
            ->assertResponseOk()
            ->assertResponseKeys(['access_token'])
            ->assertResponseKey('grant_type', 'refresh_token')
            ->assertResponseKey('expires_in', $this->config('apiAuth.jwt.maxAge'));

        return $this->getJson()['access_token'];
    }

    /** @depends test_auth_generate_200 */
    public function test_auth($token)
    {
        $this->configure('apiAuth', ['scopes' => ['/corsheader' => 'user']]);

        // Without jwt
        $this->doRequest('/corsheader')
            ->assertResponseNotOk()
            ->assertStatusCode(403)
            ->assertResponseContains('Permission denied');

        // With jwt
        $this->doRequest('/corsheader', [], ['Authorization' => "Bearer {$token['access_token']}"])
            ->assertResponseOk()
            ->assertResponseKeys(['request', 'response']);

        $this->assertSame(1, $this->di('authenticator')->getSubject());

        // With invalid jwt
        $this->doRequest('/corsheader', [], ['Authorization' => 'Bearer invalid'])
            ->assertResponseNotOk()
            ->assertStatusCode(403)
            ->assertResponseContains('Invalid token');
    }

    protected function ensureUser(array $data)
    {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);

        $this->di('db')->upsert('users', \array_intersect_key($data, [
            'username' => true, 'password' => true, 'scopes' => true,
        ]), ['username' => $data['username']]);
    }
}
