<?php

namespace PhalconExt\Mail;

use PhalconExt\Logger\LogsToFile;

/**
 * Mail logger for swift mailer.
 *
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 */
class Logger implements \Swift_Events_SendListener
{
    use LogsToFile;

    /** @var array */
    protected $config = [];

    /** @var string */
    protected $logFormat = "%message%\n\n";

    /** @var string */
    protected $fileExtension;

    public function __construct(array $config)
    {
        $this->config = $config + ['enabled' => false, 'type' => null];

        if ($this->check()) {
            $this->activate($this->config['logPath']);
        }
    }

    /**
     * Check if config is fine.
     *
     * @return bool
     */
    protected function check(): bool
    {
        if (!$this->config['enabled'] || empty($this->config['logPath'])) {
            return false;
        }

        $this->fileExtension = '.' . $this->config['type'];

        if (\in_array($this->config['type'], ['html', 'json', 'eml'])) {
            return true;
        }

        return false;
    }

    /**
     * Right before mail is sent.
     *
     * @param \Swift_Events_SendEvent $evt
     *
     * @return void
     */
    public function beforeSendPerformed(\Swift_Events_SendEvent $evt)
    {
        if (!$this->activated) {
            return;
        }

        $message = $this->formatMessage($evt->getMessage());

        $this->log($message);
    }

    /**
     * Formats message as per type.
     *
     * @param \Swift_Mime_SimpleMessage $message
     *
     * @return string
     */
    protected function formatMessage(\Swift_Mime_SimpleMessage $message): string
    {
        if ('eml' === $this->config['type']) {
            return $message->toString();
        }

        $parts = $this->getMessageParts($message);

        if ('json' === $this->config['type']) {
            return \json_encode($parts);
        }

        // Html, what else?
        return $this->formatHtml(\array_filter($parts));
    }

    /**
     * Format msg parts as html.
     *
     * @param array $parts
     *
     * @return string
     */
    protected function formatHtml(array $parts): string
    {
        $html = "<div style='text-align: center;'>\n";
        foreach ($parts as $block => $content) {
            $html .= "<h3>$block</h3>\n";

            if (\is_scalar($content)) {
                $html .= "<div class='$block'>$content</div>\n";

                continue;
            }

            foreach ((array) $content as $key => $value) {
                $html .= "<p class='$block'>$key: $value</p>\n";
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Get message parts.
     *
     * @param \Swift_Mime_SimpleMessage $message
     *
     * @return array
     */
    protected function getMessageParts(\Swift_Mime_SimpleMessage $message)
    {
        $attachments = \array_filter($message->getChildren(), function ($part) {
            return $part instanceof \Swift_Attachment;
        });

        $attachments = \array_map(function ($part) {
            return $part->getName();
        }, $attachments);

        return [
            'Subject'     => $message->getSubject(),
            'From'        => $message->getFrom(),
            'To'          => $message->getTo(),
            'Body'        => $message->getBody(),
            'Attachments' => $attachments,
        ];
    }

    /**
     * Right after mail is sent.
     *
     * @param \Swift_Events_SendEvent $evt
     *
     * @return void
     */
    public function sendPerformed(\Swift_Events_SendEvent $evt)
    {
        // Do nothing!
    }
}
