<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Test;

class MicroControllerTest extends WebTestCase
{
    public function testIndexAction()
    {
        $this->doRequest('/')
            ->assertResponseOk()
            ->assertResponseContains('This view is rendered by Twig engine in MICRO mode')
            ->assertResponseContains('Check features/extensions');
    }

    public function testDbAction()
    {
        $this->doRequest('/db')
            ->assertResponseOk()
            ->assertResponseContains('You can check sql logs in <code>example/.var/sql/</code>');
    }

    public function testDiAction()
    {
        $this->doRequest('/di')
            ->assertResponseOk();
    }

    public function testMailAction()
    {
        $this->doRequest('/mail')
            ->assertResponseOk()
            ->assertResponseContains('You can check mail logs in <code>example/.var/mail/</code>');
    }

    public function testLoggerAction()
    {
        $this->doRequest('/logger')
            ->assertResponseOk()
            ->assertResponseContains('info from echo logger')
            ->assertResponseContains('error from echo logger');
    }

    public function testValidationAction()
    {
        $this->doRequest('/validation')
            ->assertResponseNotOk()
            ->assertStatusCode(422)
            ->assertResponseContains('Field name must be at least 5 characters long')
            ->assertResponseContains('Field id is required')
            ->assertResponseContains('Field email must be an email with @gmail.com');
    }

    public function testValidationPass()
    {
        $this->doRequest('/validation', ['name' => 'Adhocore', 'id' => 12, 'email' => '.@gmail.com'])
            ->assertResponseOk();
    }

    public function testCorsAction()
    {
        $this->doRequest('/cors')
            ->assertResponseOk()
            ->assertResponseContains('CORS request will be triggered by fetching host http://localhost:1234/'
                . ' from different origin http://127.0.01:1234/');
    }

    public function testCorsHeaderAction()
    {
        $this->doRequest('/corsheader')
            ->assertResponseOk()
            ->assertResponseJson();
    }
}
