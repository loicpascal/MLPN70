<?php


namespace App\Service\Mail;


use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailService
{
    private $mailer;

    private $from = '';
    private $to = '';
    private $cc = '';
    private $bcc = '';
    private $replyTo = '';
    private $priority = Email::PRIORITY_NORMAL;

    private $subject = '';
    private $html = '';

    const EMAIL_FROM = 'nepasrepondre@malettreauperenoel.fr';

    public function __construct(MailerInterface $mailer) {
        $this->mailer = $mailer;
    }

    public function sendEmail()
    {
        $email = new Email();

        $email
            ->from($this->getFrom())
            ->to($this->getTo())
            ->subject($this->getSubject())
            ->text('Sending emails is fun again!')
            ->html($this->getHtml());

        if ($this->getCc()) {
            $email->cc($this->getCc());
        }

        if ($this->getBcc()) {
            $email->bcc($this->getBcc());
        }

        if ($this->getReplyTo()) {
            $email->replyTo($this->getReplyTo());
        }

        if ($this->getPriority()) {
            $email->priority($this->getPriority());
        }

        // Todo : Corriger le MAILER_DSN du .env pour pouvoir envoyer les mails
        $this->mailer->send($email);
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function setFrom(string $from): void
    {
        $this->from = $from;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function setTo(string $to): void
    {
        $this->to = $to;
    }

    public function getCc(): string
    {
        return $this->cc;
    }

    public function setCc(string $cc): void
    {
        $this->cc = $cc;
    }

    public function getBcc(): string
    {
        return $this->bcc;
    }

    public function setBcc(string $bcc): void
    {
        $this->bcc = $bcc;
    }

    public function getReplyTo(): string
    {
        return $this->replyTo;
    }

    public function setReplyTo(string $replyTo): void
    {
        $this->replyTo = $replyTo;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): void
    {
        $this->priority = $priority;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function getHtml(): string
    {
        return $this->html;
    }

    public function setHtml(string $html): void
    {
        $this->html = $html;
    }
}
