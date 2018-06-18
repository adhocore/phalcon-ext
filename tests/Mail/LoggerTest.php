<?php

namespace PhalconExt\Test\Mail;

use PhalconExt\Mail\Mailer;
use PhalconExt\Test\WebTestCase;

class LoggerTest extends WebTestCase
{
    protected $log;

    public function setUp()
    {
        parent::setUp();

        $this->log = $this->config('mail.logger.logPath') . date('Y-m-d');

        foreach (['.eml', '.json', '.html'] as $ext) {
            file_put_contents($this->log . $ext, '');
        }
    }

    /** @dataProvider cases */
    public function test_logs(string $type, $expected)
    {
        $this->newMailer(['type' => $type])->newMail()
            ->setTo('test@localhost')
            ->setSubject('Hey')
            ->setBody('Email body')
            ->mail();

        $log = trim(file_get_contents($this->log . '.' . $type));

        if (is_array($expected)) {
            $this->assertSame(implode("\n", $expected), $log);
        } else {
            $this->assertContains($expected, $log);
        }
    }

    public function test_doesnt_log_when_disabled()
    {
        $this->newMailer(['enabled' => false, 'type' => 'json'])->newMail()
            ->setTo('test@localhost')
            ->setSubject('Hey')
            ->setBody('Email body')
            ->mail();

        $this->assertEmpty(trim(file_get_contents($this->log . '.json')));
    }

    public function test_doesnt_log_when_type_invalid()
    {
        $this->newMailer(['enabled' => true, 'type' => 'abc'])->newMail()
            ->setTo('test@localhost')
            ->setSubject('Hey')
            ->setBody('Email body')
            ->mail();

        $this->assertFalse(is_file($this->log . '.abc'));
    }

    public function cases()
    {
        return [
            ['type' => 'html', 'expected' => [
                "<div style='text-align: center;'>",
                '<h3>Subject</h3>',
                "<div class='Subject'>Hey</div>",
                '<h3>From</h3>',
                "<p class='From'>test@localhost: Test</p>",
                '<h3>To</h3>',
                "<p class='To'>test@localhost: </p>",
                '<h3>Body</h3>',
                "<div class='Body'>Email body</div>",
                '</div>'
            ]],
            ['type' => 'json', 'expected' => [
                '{"Subject":"Hey","From":{"test@localhost":"Test"},"To":{"test@localhost":null},"Body":"Email body","Attachments":[]}',
            ]],
            ['type' => 'eml', 'expected' => "Subject: Hey\r\nFrom: Test <test@localhost>\r\nTo: test@localhost\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=utf-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\nEmail body"]
        ];
    }

    protected function newMailer(array $logger = [])
    {
        return new Mailer([
            'driver' => 'null',
            'from'   => ['email' => 'test@localhost', 'name' => 'Test'],
            'logger' => $logger + ['enabled' => true, 'logPath' => __DIR__ . '/../../example/.var/mail/'],
        ]);
    }
}
