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

use Phalcon\Di;
use Phalcon\Logger\Logger;
use Phalcon\Logger\Adapter\Stream as FileLogger;
use Phalcon\Logger\Formatter\Line as LineFormatter;

/**
 * Provides di service.
 *
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 */
trait LogsToFile
{
    /** @var bool */
    protected $activated = false;

    /** @var FileLogger */
    protected $logger;

    /**
     * Log the given message and level. Interpolates message if applicable from context.
     *
     * @param string $message
     * @param int    $level
     * @param array  $context
     *
     * @return void
     */
    public function log(string $message, int $level = Logger::DEBUG, array $context = [])
    {
        if (!$this->activated) {
            return;
        }

        $this->logger->log($level, $message, $context);
    }

    /**
     * Activate the logger.
     *
     * @param string $logPath
     *
     * @return void
     */
    protected function activate(string $logPath)
    {
        $logPath = \rtrim($logPath, '/\\') . '/';

        $this->activated = true;

        $file = new FileLogger($logPath . \date('Y-m-d') . $this->fileExtension);
        $file->setFormatter(new LineFormatter($this->logFormat ?? '[%date%][%level%] %message%'));

        $this->logger  = new Logger('logger.file', ['main' => $file]);
    }
}
