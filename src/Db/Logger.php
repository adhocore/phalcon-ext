<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Db;

use Phalcon\Db\Adapter\AbstractAdapter;
use Phalcon\Events\Event;
use PhalconExt\Logger\LogsToFile;

/**
 * SQL logger implemented as db event listener.
 *
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 */
class Logger
{
    use LogsToFile;

    /** @var int The current count of fired sqls */
    protected $index = -1;

    /** @var array */
    protected $config = [];

    /** @var string */
    protected $logFormat = "%message%;\n";

    /** @var string */
    protected $fileExtension = '.sql';

    public function __construct(array $config)
    {
        $this->config = $config + ['skipFirst' => 0, 'addHeader' => false, 'backtraceLevel' => 0];

        if (!empty($this->config['enabled']) && !empty($this->config['logPath'])) {
            $this->activate($this->config['logPath']);
        }
    }

    /**
     * Run before firing query.
     *
     * @param Event           $event
     * @param AbstractAdapter $connection
     *
     * @return void
     */
    public function beforeQuery(Event $event, AbstractAdapter $connection)
    {
        if (!$this->activated) {
            return;
        }

        if (++$this->index < $this->config['skipFirst']) {
            return;
        }

        $this->log($this->getHeader() . $this->getBacktrace() . $this->getParsedSql($connection));
    }

    /**
     * Get log header like request uri and datetime.
     *
     * @return string
     */
    protected function getHeader(): string
    {
        if ($this->index !== $this->config['skipFirst'] || !$this->config['addHeader']) {
            return '';
        }

        $header = '-- ' . ($_SERVER['REQUEST_URI'] ?? '') . \date(' [Y-m-d H:i:s]') . "\n";

        return $header
            . \str_pad('-- -', \strlen($header), '-', STR_PAD_RIGHT)
            . "\n";
    }

    /**
     * Get the backtrace of paths leading to logged query.
     *
     * @return string
     */
    protected function getBacktrace(): string
    {
        if ($this->config['backtraceLevel'] < 1) {
            return '';
        }

        $trace = '';
        foreach (\array_slice(\debug_backtrace(), 2, $this->config['backtraceLevel']) as $tr) {
            $tr += ['function' => 'n/a', 'line' => 0, 'file' => 'n/a'];

            $trace .= "  -- {$tr['file']}:{$tr['line']}#{$tr['function']}()\n";
        }

        return $trace;
    }

    /**
     * Get the properly interpolated sql.
     *
     * @param AbstractAdapter $connection
     *
     * @return string
     */
    protected function getParsedSql(AbstractAdapter $connection): string
    {
        $parts = $connection->convertBoundParams(
            $connection->getSqlStatement(),
            $connection->getSQLVariables() ?: []
        );

        $binds = $parts['params'] ?: $connection->getSQLVariables();

        return \vsprintf(\str_replace('?', "'%s'", $parts['sql']), $binds);
    }

    /**
     * Run after firing query.
     *
     * @param Event   $event
     * @param Adapter $connection
     *
     * @return void
     */
    public function afterQuery(Event $event, $connection)
    {
        // Do nothing.
    }
}
