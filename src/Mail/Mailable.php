<?php

namespace PhalconExt\Mail;

use Phalcon\Di;

trait Mailable
{
    public function mail(string $to, string $subject, array $options = [])
    {
        $mailer = Di::getDefault()->resolve('mailer');

        $mail = ($options['template'] ?? null)
            ? $mailer->newTemplateMail($options['template'], $options['params'] ?? [])
            : $mailer->newMail()->setBody($options['body']);

        return $mail->setTo($to)->setSubject($subject)->mail();
    }
}
