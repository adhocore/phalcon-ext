<?php

namespace PhalconExt\Mail;

use Phalcon\Di;

trait Mailable
{
    /**
     * Mail.
     *
     * @param mixed  $to      Recepient address(es) [array/string]
     * @param string $subject
     * @param array  $options The body &/or view template.
     *
     * @return int
     */
    public function mail($to, string $subject, array $options = []): int
    {
        $mailer = Di::getDefault()->resolve('mailer');

        $mail = ($options['template'] ?? null)
            ? $mailer->newTemplateMail($options['template'], $options['params'] ?? [])
            : $mailer->newMail()->setBody($options['body']);

        return $mail->setTo($to)->setSubject($subject)->mail();
    }
}
