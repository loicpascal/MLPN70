<?php


namespace App\Service\Mail;


use App\Entity\Comment;
use App\Entity\Idea;
use App\Entity\Member;
use Symfony\Component\HttpFoundation\Request;

class MailCommentService extends MailService
{
    public function sendEmailNewComment(Member $member, Idea $idea, Comment $comment, Request $request, string $url)
    {
        $urlSite = $request->getScheme() . '://' . $request->getHttpHost();

        $body = '<p>Bonjour,</p>';
        $body .= $member->getFirstname() . " a ajouté un commentaire sur votre idée <b>" . $idea->getTitle() . "</b>.";
        $body .= '<br><br><b>Commentaire :</b><br>';
        $body .= '---<br>' . nl2br(htmlspecialchars($comment->getContent())) . '<br>---';

        $link = $urlSite . $url;
        $body .= '<p><a href="' . $link . '">Répondre au commentaire</a></p>';
        $body .= '<p>À bientôt sur votre site !<br><a href="' . $urlSite . '">' . $request->getHttpHost() . '</a></p>';

        $this->setFrom(MailService::EMAIL_FROM);
        $this->setTo($idea->getMember()->getEmail());
        $this->setHtml($body);

        $this->sendEmail();
    }
}
