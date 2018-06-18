<?php

namespace PhalconExt\Test\Mail;

use PhalconExt\Mail\Mailer;
use PhalconExt\Test\WebTestCase;

class MailerTest extends WebTestCase
{
    public function test_no_driver_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The mailer config is invalid, missing driver &/or identity');

        new Mailer([]);
    }

    public function test_no_host_port_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The mailer config is invalid, missing smtp host &/or port');

        new Mailer([
            'driver' => 'smtp',
            'from'   => ['name' => 'Test', 'email' => 'test@localhost'],
        ]);
    }

    public function test_new_mail()
    {
        $mail = $this->newMailer()->newMail();

        $this->assertInstanceOf(\Swift_Message::class, $mail);

        $this->assertSame(['test@localhost' => 'Test'], $mail->getFrom());
    }

    public function test_transport()
    {
        $transport = $this->newMailer('smtp')->getTransport();

        $this->assertInstanceOf(\Swift_Transport::class, $transport);
        $this->assertInstanceOf(\Swift_SmtpTransport::class, $transport);

        $transport = $this->newMailer('sendmail')->getTransport();

        $this->assertInstanceOf(\Swift_Transport::class, $transport);
        $this->assertInstanceOf(\Swift_SendmailTransport::class, $transport);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Mail driver "hmm" not supported');

        $transport = $this->newMailer('hmm')->getTransport();
    }

    public function test_new_template_mail()
    {
        $mail = $this->newMailer()->newTemplateMail('mail.template');

        $this->assertSame('<p>Email content</p>', trim($mail->getBody()), 'Should parse twig view');
        $this->assertSame('text/html', $mail->getContentType(), 'Should load view as html');
    }

    public function test_attachments()
    {
        $mail = $this->newMailer('sendmail')->newMail();

        $mail->attachFiles([__FILE__, __DIR__ . '/LoggerTest.php']);
        $mail->attachRaw('Raw plain text data', 'rawtext.txt', 'text/plain');

        $children = $mail->getChildren();

        $this->assertCount(3, $children, 'Should have 2 attachments');

        $file = $children[0];

        $this->assertInstanceOf(\Swift_Attachment::class, $file, 'Should be instanceof \Swift_Attachment');
        $this->assertSame('application/x-php', $file->getContentType());
        $this->assertSame('MailerTest.php', $file->getFilename());

        $raw = $children[2];

        $this->assertInstanceOf(\Swift_Attachment::class, $raw, 'Should be instanceof \Swift_Attachment');
        $this->assertSame('text/plain',  $raw->getContentType());
        $this->assertSame('rawtext.txt', $raw->getFilename());
    }

    public function test_mail()
    {
        $mail = $this->newMailer('null')->newMail()->setSubject('Test')->setBody('test content');

        $sentCount = $mail->setTo(['test@localhost' => 'Test'])->mail();

        $this->assertSame(1, $sentCount, 'Should have sent to 1 recepient only');

        $sentCount = $mail->setTo(['test1@localhost' => 'Test1', 'test2@localhost' => 'Test2'])->mail();

        $this->assertSame(2, $sentCount, 'Should have sent to 2 recepients');
    }

    protected function newMailer(string $driver = 'smtp')
    {
        return new Mailer([
            'driver'     => $driver,
            'host'       => 'http://localhost',
            'port'       => 25,
            'encryption' => 'tls',
            'username'   => 'test@localhost',
            'password'   => 'p4sswrd',
            'from'       => ['email' => 'test@localhost', 'name' => 'Test'],
        ]);
    }
}
