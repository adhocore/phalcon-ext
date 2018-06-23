<?php

namespace PhalconExt\Contract;

/**
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 */
interface ApiAuthenticator
{
    public function configure(array $config);

    public function byCredential(string $username, string $password): bool;

    public function byRefreshToken(string $refreshToken): bool;

    public function bySubject(int $subject): bool;

    public function createRefreshToken(): string;

    public function getSubject(): int;

    public function getScopes(): array;
}
