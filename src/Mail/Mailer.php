<?php

namespace PhalconExt\Mail;

use Phalcon\Di;
use Phalcon\Mvc\View\Simple;

/**
 * A swift mailer tuned for phalcon.
 *
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 */
class Mailer
{
    /** @var array */
    protected $config = [];

    /** @var \Swift_Transport */
    protected $transport;

    /** @var \Swift_Mailer */
    protected $mailer;

    /** @var array */
    public $failed = [];

    public function __construct(array $config)
    {
        if (!isset($config['driver'], $config['from']['email'], $config['from']['name'])) {
            throw new \InvalidArgumentException('The mailer config is invalid, missing driver &/or identity');
        }

        if ($config['driver'] === 'smtp' && !isset($config['host'], $config['port'])) {
            throw new \InvalidArgumentException('The mailer config is invalid, missing smtp host &/or port');
        }

        $this->config = $config;
    }

    /**
     * Create new raw mail.
     *
     * @return Mail
     */
    public function newMail(): Mail
    {
        $from = $this->config['from'];

        return (new Mail($this))->setFrom($from['email'], $from['name']);
    }

    /**
     * Create new mail from view template.
     *
     * @param string $viewFile
     * @param array  $viewParams
     * @param string $type
     *
     * @return Mail
     */
    public function newTemplateMail(string $viewFile, array $viewParams = [], string $type = 'text/html'): Mail
    {
        $dirName  = \dirname($viewFile);
        $fileName = \basename($viewFile);
        $view     = Di::getDefault()->getShared('view');

        $markup = $view instanceof Simple
            ? $view->render($viewFile, $viewParams)
            : $view->start()->setVars($viewParams)->render($dirName, $fileName)->finish()->getContent();

        return $this->newMail()->setBody($markup, $type);
    }

    /**
     * Get the swift mailer.
     *
     * @throws \InvalidArgumentException When configured driver not supported.
     *
     * @return \Swift_Mailer
     */
    public function getMailer(): \Swift_Mailer
    {
        return $this->mailer ?? $this->mailer = new \Swift_Mailer($this->getTransport());
    }

    /**
     * Get the swift transport.
     *
     * @throws \InvalidArgumentException When configured driver not supported.
     *
     * @return \Swift_Transport
     */
    public function getTransport(): \Swift_Transport
    {
        if (!$this->transport) {
            $this->transport = $this->initTransport();

            if (null !== $loggerConfig = $this->config['logger'] ?? null) {
                $this->transport->registerPlugin(new Logger($loggerConfig));
            }
        }

        return $this->transport;
    }

    /**
     * Instantiate the swift transport.
     *
     * @throws \InvalidArgumentException When configured driver not supported.
     *
     * @return \Swift_Transport
     */
    protected function initTransport(): \Swift_Transport
    {
        $config = $this->config;
        $driver = \strtolower($config['driver']);

        if ('null' === $driver) {
            return new \Swift_NullTransport;
        }

        if ('sendmail' === $driver) {
            return new \Swift_SendmailTransport($config['sendmail'] ?? '/usr/sbin/sendmail -bs');
        }

        if ('smtp' !== $driver) {
            throw new \InvalidArgumentException(sprintf('Mail driver "%s" not supported', $driver));
        }

        $transport = (new \Swift_SmtpTransport)->setHost($config['host'])->setPort($config['port']);

        if ($config['encryption'] ?? null) {
            $this->transport->setEncryption($config['encryption']);
        }

        if ($config['username'] ?? null) {
            $transport->setUsername($config['username'])->setPassword($config['password']);
        }

        return $transport;
    }

    /**
     * Mail the mail with swift mailer.
     *
     * @return int The count of mailed recipients.
     */
    public function mail(Mail $mail)
    {
        $this->failed = [];

        return $this->getMailer()->send($mail, $this->failed);
    }
}
