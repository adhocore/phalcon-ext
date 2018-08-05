<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Contract;

/**
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 */
interface ApiAuthenticator
{
    /**
     * Configure the authenticator.
     *
     * @param array $config Config parameters
     *
     * @return void
     */
    public function configure(array $config);

    /**
     * Attempt to auhenticate by credential.
     *
     * The implementer must know and cache who has been logged for this session.
     *
     * @param string $username Login id/username/email etc
     * @param string $password
     *
     * @return bool True on success, false if not.
     */
    public function byCredential(string $username, string $password): bool;

    /**
     * Attempt to auhenticate by refresh token.
     *
     * The implementer must know and cache who has been logged for this session.
     *
     * @param string $refreshToken
     *
     * @return bool True on success, false if not.
     */
    public function byRefreshToken(string $refreshToken): bool;

    /**
     * Attempt to auhenticate by subject ID.
     *
     * The implementer must know and cache who has been logged for this session.
     *
     * @param int $subject
     *
     * @return bool True on success, false if not.
     */
    public function bySubject(int $subject): bool;

    /**
     * Create a new refresh token for currently logged in user.
     *
     * The implementer must know and permanently store which token belongs to which user.
     *
     * @return string
     */
    public function createRefreshToken(): string;

    /**
     * Get the subject ID for use in `sub` claim of JWT. Ideally user ID.
     *
     * @return int
     */
    public function getSubject(): int;

    /**
     * Get the permission scopes allowed and entertainable by currenlty logged user.
     *
     * @return array
     */
    public function getScopes(): array;
}
