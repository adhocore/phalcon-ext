<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Logger;

use Phalcon\Logger\Adapter as LoggerAdapter;
use Phalcon\Logger\Formatter\Line as LineFormatter;

/**
 * Echo logger targeted for CLI environment.
 *
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 */
class EchoLogger extends LoggerAdapter
{
    /** @var array */
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;

        if ($config['level'] ?? null) {
            $this->setLogLevel($config['level']);
        }
    }

    /**
     * Echoes the log message as it is in CLI!
     */
    protected function logInternal(string $message, int $type, int $time, array $context)
    {
        echo $this->getFormatter()->format($message, $type, $time, $context);
    }

    /**
     * Gets a plain line formatter with configured format.
     *
     * @return LineFormatter
     */
    public function getFormatter(): LineFormatter
    {
        if (!$this->_formatter) {
            $this->_formatter = new LineFormatter(
                $this->config['format'] ?? '[%type%][%date%] %message%',
                $this->config['date_format'] ?? 'Y-m-d H:i:s'
            );
        }

        return $this->_formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }
}
