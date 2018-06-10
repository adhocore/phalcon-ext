<?php

use PhalconExt\Di\ProvidesDi;
use Phalcon\Mvc\Controller;
use PhalconExt\Mail\Mailable;

/** MVC controller */
class IndexController extends Controller
{
    use Mailable;
    use ProvidesDi;

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

    public function corsAction()
    {
        return $this->view->setVars(['cors_uri' => 'mvc.php?_url=/corsheader']);
    }

    public function corsHeaderAction()
    {
        $response = $this->di('response');

        return $response->setJsonContent([
            'request' => $this->di('request')->getHeaders(),
            'response' => $response->getHeaders()->toArray(),
        ]);
    }
}
