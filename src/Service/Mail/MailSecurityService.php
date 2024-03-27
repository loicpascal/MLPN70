<?php


namespace App\Service\Mail;


use App\Entity\Comment;
use App\Entity\Idea;
use App\Entity\Member;
use Symfony\Component\HttpFoundation\Request;

class MailSecurityService extends MailService
{
    public function sendEmailPasswordForgotten(Member $member, Request $request, string $url)
    {
        $urlSite = $request->getScheme() . '://' . $request->getHttpHost();
        $link = $urlSite . $url;

        $body = '<p>Bonjour ' . $member->getFirstname() . ',</p>';
        $body .= "Cliquez sur le lien suivant ou copiez-le dans votre navigateur afin de redéfinir votre mot de passe : " . $link . "</b>.";
        $body .= '<p>À bientôt sur votre site !<br><a href="' . $urlSite . '">' . $request->getHttpHost() . '</a></p>';

        // todo: remplacer par MailService::EMAIL_FROM
//        $this->setFrom(MailService::EMAIL_FROM);
        $this->setFrom('loic.pascal@gmail.com');
        $this->setTo($member->getEmail());
        $this->setHtml($body);

        $this->sendEmail();
    }
}
