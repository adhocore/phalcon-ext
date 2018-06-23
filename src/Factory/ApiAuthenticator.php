<?php

namespace PhalconExt\Factory;

use Phalcon\Config;
use Phalcon\Db\Adapter as Db;
use Phalcon\Security\Random;
use PhalconExt\Contract\ApiAuthenticator as Contract;

/**
 * Factory implementation for ApiAuthenticator. You might want to roll out your own if it doesnt suffice.
 *
 * Uses `users` table with `id`, `username`, `password`, `scopes`, `created_at` fields.
 * And `tokens` table with `id`, `user_id`, `type`, `token`, `created_at`, `expire_at` fields.
 */
class ApiAuthenticator implements Contract
{
    protected $db;

    protected $user;

    protected $config = [];

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    public function configure(array $config)
    {
        $this->config = $config;
    }

    public function byCredential(string $username, string $password): bool
    {
        $this->user = $this->findUser('username', $username, $password);

        return !empty($this->user);
    }

    protected function findUser(string $column, $value, $password = null): array
    {
        $user = $this->db->fetchOne("SELECT * FROM users WHERE $column = ?", \PDO::FETCH_ASSOC, [$value]);
        $hash = $user['password'] ?? null;

        if ($password && !\password_verify($password, $hash)) {
            return [];
        }

        unset($user['password']);

        return $user ?: [];
    }

    public function byRefreshToken(string $refreshToken): bool
    {
        $token = $this->db->fetchOne(
            'SELECT * FROM tokens WHERE type = ? AND token = ?',
            \PDO::FETCH_ASSOC,
            ['refresh', $refreshToken]
        );

        if (!$token /* @todo check if token expired */) {
            return false;
        }

        return $this->bySubject($token['user_id']);
    }

    public function bySubject(int $subject): bool
    {
        $this->user = $this->findUser('id', $subject);

        return !empty($this->user);
    }

    public function createRefreshToken(): string
    {
        $this->mustBeAuthenticated();

        $tokenLen     = \min($this->config['tokenLength'] ?? 0, 32);
        $prefix       = \substr($this->config['tokenPrefix'] ?? '', 0, 4);
        $random       = (new Random)->bytes($tokenLen - \strlen($prefix));
        $refreshToken = $prefix . \bin2hex($random);

        $this->db->insertAsDict('tokens', [
            'type'    => 'refresh',
            'token'   => $refreshToken,
            'user_id' => $this->user['id'],
        ]);

        return $refreshToken;
    }

    /**
     * Throw up if user is not authenticated yet.
     *
     * @codeCoverageIgnore
     *
     * @throws \LogicException
     */
    protected function mustBeAuthenticated()
    {
        if (!$this->user) {
            throw new \LogicException('You must authenticate an user first');
        }
    }

    public function getSubject(): int
    {
        $this->mustBeAuthenticated();

        return $this->user['id'];
    }

    public function getScopes(): array
    {
        $this->mustBeAuthenticated();

        return \explode(',', $this->user['scopes']);
    }
}
