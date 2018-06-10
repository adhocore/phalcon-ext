<?php

namespace PhalconExt\Db;

use Phalcon\Events\Event;
use PhalconExt\Logger\LogsToFile;

/**
 * SQL logger implemented as db event listener.
 */
class Logger
{
    use LogsToFile;

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

    public function beforeQuery(Event $event, $connection)
    {
        if (!$this->activated) {
            return;
        }

        static $index = -1;

        if (++$index < $this->config['skipFirst']) {
            return;
        }

        $this->log($this->getHeader($index) . $this->getBacktrace() . $this->getParsedSql($connection));
    }

    protected function getHeader(int $index)
    {
        if ($index !== $this->config['skipFirst'] || !$this->config['addHeader']) {
            return '';
        }

        $header = '-- ' . ($_SERVER['REQUEST_URI'] ?? '') . \date(' [Y-m-d H:i:s]') . "\n";

        return $header
            . \str_pad("-- -", \strlen($header), '-', STR_PAD_RIGHT)
            . "\n";
    }

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

    protected function getParsedSql($connection)
    {
        $sql = $connection->getSqlStatement();

        if ([] === $bind = $connection->getSQLVariables() ?: []) {
            return $sql;
        }

        // Positional placeholder `?`.
        if (\array_key_exists(0, $bind)) {
            return \vsprintf(\str_replace('?', "'%s'", $sql), $bind);
        }

        // Named placeholder `:name`!
        foreach ($bind as $key => $value) {
            $sql = \preg_replace("/:$key/", "'$value'", $sql);
        }

        return $sql;
    }

    public function afterQuery(Event $event, $connection)
    {
        // Do nothing.
    }
}
