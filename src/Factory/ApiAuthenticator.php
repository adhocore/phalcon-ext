<?php

namespace PhalconExt\Factory;

use Phalcon\Db\Adapter as Db;
use Phalcon\Security\Random;
use PhalconExt\Contract\ApiAuthenticator as Contract;

/**
 * Factory implementation for ApiAuthenticator. You might want to roll out your own if it doesnt suffice.
 *
 * Uses `users` table with `id`, `username`, `password`, `scopes`, `created_at` fields.
 * And `tokens` table with `id`, `user_id`, `type`, `token`, `created_at`, `expire_at` fields.
 *
 * You can access the current authenticator application wide as: `$di->get('authenticator')`.
 */
class ApiAuthenticator implements Contract
{
    /** @var Db */
    protected $db;

    /** @var array User data */
    protected $user = [];

    /** @var array Config options */
    protected $config = [];

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(array $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function byCredential(string $username, string $password): bool
    {
        $this->user = $this->findUser('username', $username, $password);

        return !empty($this->user);
    }

    /**
     * Find user based on given property and value. If password is provided it it validated.
     *
     * @param string      $column
     * @param int|string  $value
     * @param null|string $password
     *
     * @return array User detail, empty on failure.
     */
    protected function findUser(string $column, $value, $password = null): array
    {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE $column = ?",
            \PDO::FETCH_ASSOC,
            [$value]
        );

        $hash = $user['password'] ?? null;
        if ($password && !\password_verify($password, $hash)) {
            return [];
        }

        unset($user['password']);

        return $user ?: [];
    }

    /**
     * {@inheritdoc}
     */
    public function byRefreshToken(string $refreshToken): bool
    {
        $token = $this->db->fetchOne(
            'SELECT * FROM tokens WHERE type = ? AND token = ?',
            \PDO::FETCH_ASSOC,
            ['refresh', $refreshToken]
        );

        if (!$token || new \DateTime > new \DateTime($token['expire_at'])) {
            return false;
        }

        return $this->bySubject($token['user_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function bySubject(int $subject): bool
    {
        $this->user = $this->findUser('id', $subject);

        return !empty($this->user);
    }

    /**
     * {@inheritdoc}
     */
    public function createRefreshToken(): string
    {
        $this->mustBeAuthenticated();

        $tokenLen     = \min($this->config['tokenLength'] ?? 0, 32);
        $prefix       = \substr($this->config['tokenPrefix'] ?? '', 0, 4);
        $random       = (new Random)->bytes($tokenLen - \strlen($prefix));
        $refreshToken = $prefix . \bin2hex($random);

        $this->db->insertAsDict('tokens', [
            'type'       => 'refresh',
            'token'      => $refreshToken,
            'user_id'    => $this->user['id'],
            'created_at' => \date('Y-m-d H:i:s'),
            'expire_at'  => \date('Y-m-d H:i:s', \time() + $this->config['refreshMaxAge']),
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

    /**
     * {@inheritdoc}
     */
    public function getSubject(): int
    {
        $this->mustBeAuthenticated();

        return $this->user['id'];
    }

    /**
     * {@inheritdoc}
     */
    public function getScopes(): array
    {
        $this->mustBeAuthenticated();

        return \explode(',', $this->user['scopes']);
    }
}
