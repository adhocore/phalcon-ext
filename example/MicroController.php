<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https//:github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Example;

use Phalcon\Logger;
use PhalconExt\Di\ProvidesDi;
use PhalconExt\Logger\EchoLogger;
use PhalconExt\Mail\Mailer;
use PhalconExt\Test\Di\DeepNest;
use PhalconExt\Test\Di\DiProvider;
use PhalconExt\Test\Di\NeedsDb;

/** Micro Controller */
class MicroController
{
    use ProvidesDi;

    public function indexAction()
    {
        $this->di('response')->setHeader('Content-Type', 'text/html');

        return $this->di('view')->render('twig.view', ['engine' => 'Twig', 'mode' => 'MICRO']);
    }

    public function dbAction()
    {
        $db = $this->di('db');

        $info['bulk_insert=1'] = (int) $db->insertAsBulk('tests', [
            ['prop_a' => 1, 'prop_c' => 3],
            ['prop_b' => 2, 'prop_a' => 3],
            ['prop_c' => 2, 'prop_b' => 1],
        ]);

        $info['count_by[prop_a:1]=1']          = $db->countBy('tests', ['prop_a' => 1]);
        $info['count_by[prop_b:1,prop_c:3]=0'] = $db->countBy('tests', ['prop_b' => 1, 'prop_c' => 3]);

        $info['upsert=1']             = (int) $db->upsert('tests', ['prop_b' => 2], ['prop_b' => 1]);
        $info['count_by[prop_b:2]=1'] = $db->countBy('tests', ['prop_b' => 2]);

        return '<pre>' . print_r($info, 1) . '</pre>'
            . '<p>You can check sql logs in <code>example/.var/sql/</code></p>';
    }

    public function diAction()
    {
        $this->di()->registerAliases([
            'theTwig'              => 'twig',
            'Phalcon\\Db\\Adapter' => 'db',     // needs alias as `NeedsDb` has its name `_db`
            // Mailer::class       => 'mailer', // no need- `DeepNest` uses known name `mailer`
        ]);

        $info['alias[theTwig,twig]=1'] = $this->di('theTwig') === $this->di('twig');

        $info['resolve[NeedsDb]   =1'] = $this->di(NeedsDb::class) instanceof NeedsDb;
        $info['resolve[DeepNest]  =1'] = $this->di(DeepNest::class) instanceof DeepNest;

        $info['has(NeedsDb)       =1'] = (int) $this->di()->has('NeedsDb');
        $info['ProvidesDi(di)     =1'] = (int) ((new DiProvider)->di() instanceof \Phalcon\Di);

        $this->di()->replace(['twig' => new \stdClass]);
        $info['replace[twig]=stdClass'] = get_class($this->di('twig'));
        $this->di()->restore();
        $info['restore[twig]=PhalconExt\View\Twig'] = get_class($this->di('twig'));

        return '<pre>' . print_r($info, 1) . '</pre>';
    }

    public function mailAction()
    {
        $info['newMail[mail]=1'] = $this->di('mailer')->newMail()
            ->setTo('me@localhost')->setSubject('Hi')->setBody('Hello')->mail();

        $info['newTemplateMail[mail]=1'] = $this->di('mailer')->newTemplateMail('mail.template')
           ->setTo('me@localhost')->setSubject('Hi')->mail();

        return '<pre>' . print_r($info, 1) . '</pre>'
            . '<p>You can check mail logs in <code>example/.var/mail/</code></p>';
    }

    public function loggerAction()
    {
        $echo = $this->di(EchoLogger::class, ['config' => ['level' => Logger::INFO]]);

        ob_start();
        $echo->log('info from echo logger<br>', Logger::INFO);
        $echo->log('debug from echo logger<br>', Logger::DEBUG); // will not print
        $echo->log('error from echo logger<br>', Logger::ERROR);

        return ob_get_clean();
    }

    public function validationAction()
    {
        $validation = $this->di('validation'); // or $this->di('validator');

        // Register new validation rule (if it is used app wide- register when defining in di)
        $validation->register('gmail', function ($data) {
            return stripos($this->getCurrentValue(), '@gmail.com') > 0;
        }, 'Field :field must be an email with @gmail.com');

        $rules = [
            'name' => [
                'required' => true,
                'length'   => ['min' => 5, 'max' => 15],
            ],
            'id'    => 'required|length:min:1;max:2;|in:domain:1,12,30',
            'email' => [
                'required' => true,
                'gmail'    => true,
            ],
            // validate if only exist in dataset
            'x' => 'length:5|if_exist',
        ];

        // Validate against query data
        $validation->run($rules, $this->di('request')->getQuery());

        $info['pass=0']           = (int) $validation->pass();
        $info['fail=1']           = (int) $validation->fail();
        $info['errors=[0,1...6]'] = $validation->getErrorMessages();

        if ($validation->fail()) {
            $this->di('response')->setStatusCode(422);
        }

        return '<pre>' . print_r($info, 1) . '<pre>';
    }

    public function corsAction()
    {
        return $this->di('view')->render('index/cors', ['cors_uri' => '?_url=/corsheader']);
    }

    public function corsHeaderAction()
    {
        $response = $this->di('response');

        return $response->setJsonContent([
            'request'  => $this->di('request')->getHeaders(),
            'response' => $response->getHeaders()->toArray(),
        ]);
    }

    public function authAction()
    {
        // Do nothing. (It will be intercepted and handled by ApiAuth middleware).
    }
}
