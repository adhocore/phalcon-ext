<?php

use Phalcon\Mvc\Controller;
use PhalconExt\Mail\Mailable;

/** MVC controller */
class IndexController extends Controller
{
    use Mailable;

    public function indexAction()
    {
        $this->view->setVars(['engine' => 'Twig', 'mode' => 'MVC']);
    }

    public function mailAction()
    {
        $info['newTemplateMail[mailable]=1'] = $this->mail('me@localhost', 'Hi', [
            'body' => 'mailable body'
        ]);

        $info['newTemplateMail=2'] = $this->mailer->newTemplateMail('mail.template')
            ->setTo(['me@localhost', 'test@localhost'])
            ->setSubject('Hi')
            ->mail();

        $this->view->setVar('info', print_r($info, 1));
    }
}
