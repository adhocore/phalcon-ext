<?php

namespace PhalconExt\Mail;

use PhalconExt\Logger\LogsToFile;

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

    public function beforeSendPerformed(\Swift_Events_SendEvent $evt)
    {
        if (!$this->activated) {
            return;
        }

        $message = $this->formatMessage($evt->getMessage());

        $this->log($message);
    }

    protected function formatMessage(\Swift_Mime_SimpleMessage $message)
    {
        if ('eml' === $this->config['type']) {
            return $message->toString();
        }

        $parts = $this->getMessageParts($message);

        if ('json' === $this->config['type']) {
            return \json_encode($parts);
        }

        // Html, what else?
        $html = "<div style='text-align: center;'>\n";
        foreach ($parts as $block => $content) {
            if (empty($content)) {
                continue;
            }

            $html .= "<h3>$block</h3>\n";

            if (\is_scalar($content)) {
                $html .= "<div class='$block'>$content</div>\n";

                continue;
            }

            foreach ((array) $content as $key => $value) {
                $html .= "<p class='$block'>$key: $value</p>\n";
            }
        }

        $html .= "</div>";

        return $html;
    }

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

    public function sendPerformed(\Swift_Events_SendEvent $evt)
    {
        // Do nothing!
    }
}
