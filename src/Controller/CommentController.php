<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Idea;
use App\Entity\Member;
use App\Form\CommentType;
use App\Service\Mail\MailCommentService;
use App\Service\Mail\MailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CommentController extends AbstractController
{
    /**
     * @Route("/comment/new/{idea_id}", name="comment_new")
     */
    public function newAction(Request $request, $idea_id, MailCommentService $mailer)
    {
        $comment = new Comment();

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $form->getData();

            $idea = $this->getDoctrine()->getRepository(Idea::class)->find($idea_id);
            $member = $this->getDoctrine()->getRepository(Member::class)->find($this->getUser()->getId());
            $em = $this->getDoctrine()->getManager();


            $comment->setMember($member);
            $comment->setIdea($idea);
            $em->persist($comment);
            $em->flush();

            /**
             * On envoie un mail au membre concerné seulement si :
             * - le membre souhaite recevoir des notifications par mail
             * - le membre de l'idée est le membre qui l'a déposée
             */
            if (
                $idea->getMember()->getReceiveEmailNewComment() &&
                $idea->getMember()->getId() &&
                ! $idea->getMemberAdding()
            ) {
                // todo: traiter l'envoi d'email
                $mailer->sendEmailNewComment($member, $idea, $comment, $request, $this->generateUrl('idea_update', ['id' => $idea->getId()]));
            }

            return $this->redirectToRoute(
                ($this->getUser()->getId() === $idea->getMember()->getId()) ? 'idea_update' : 'idea_show',
                ['id' => $idea_id]
            );
        }
        return $this->render('comment/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/comment/{id}/delete", name="comment_delete", requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $comment = $em->getRepository(Comment::class)->find($id);

        $em->remove($comment);
        $em->flush();

        return $this->redirectToRoute(
            ($this->getUser()->getId() == $comment->getIdea()->getMember()->getId()) ? 'idea_update' : 'idea_show',
            ['id' => $comment->getIdea()->getId()]
        );
    }
}
