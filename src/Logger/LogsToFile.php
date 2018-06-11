<?php

namespace PhalconExt\Logger;

use Phalcon\Di;
use Phalcon\Logger;
use Phalcon\Logger\Adapter\File as FileLogger;
use Phalcon\Logger\Formatter\Line as LineFormatter;

/**
 * Provides di service.
 */
trait LogsToFile
{
    /** @var bool */
    protected $activated = false;

    /** @var FileLogger */
    protected $logger;

    public function log(string $message, int $level = Logger::DEBUG, array $context = [])
    {
        if (!$this->activated) {
            return;
        }

        $this->logger->log($message, $level, $context);
    }

    protected function activate(string $logPath)
    {
        $logPath = \rtrim($logPath, '/\\') . '/';

        $this->activated = true;
        $this->logger    = new FileLogger($logPath . \date('Y-m-d') . $this->fileExtension);

        $this->logger->setFormatter(new LineFormatter($this->logFormat ?? null));
    }
}
