<?php

namespace PhalconExt\Mail;

class Mail extends \Swift_Message
{
    /** @var Mailer */
    protected $mailer;

    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;

        parent::__construct();
    }

    /**
     * Attach files.
     *
     * @param array $filePaths
     *
     * @return $this
     */
    public function attachFiles(array $filePaths): self
    {
        foreach ($filePaths as $filePath) {
            $this->attachFile($filePath);
        }

        return $this;
    }

    /**
     * Attach file.
     *
     * @param string      $filePath
     * @param string|null $alias
     *
     * @return self
     */
    public function attachFile(string $filePath, string $alias = null): self
    {
        return $this->attach(
            \Swift_Attachment::fromPath($filePath)->setFilename($alias ?? \basename($filePath))
        );
    }

    /**
     * Attach raw data.
     *
     * @param string $data
     * @param string $alias
     * @param string $type
     *
     * @return self
     */
    public function attachRaw(string $data, string $alias, string $type): self
    {
        return $this->attach(
            new \Swift_Attachment($data, $alias, $type)
        );
    }

    /**
     * Mail the mail with swift mailer.
     *
     * @return int The count of mailed recipients.
     */
    public function mail()
    {
        return $this->mailer->mail($this);
    }
}
