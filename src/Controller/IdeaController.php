<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Family;
use App\Entity\Idea;
use App\Entity\Member;
use App\Form\CommentType;
use App\Form\IdeaType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class IdeaController extends AbstractController
{
    /**
     * @Route("/idea", methods={"GET"}, name="idea_list")
     */
    public function listAction(SessionInterface $session)
    {
        $ideas = $this->getDoctrine()
            ->getRepository(Idea::class)
            ->findBy(
                [
                    'member' => $this->getUser(),
                    'member_adding' => null,
                    'archived' => false,
                    'family' => $session->get('family')
                ],
                ['id' => 'DESC']
            );

        return $this->render('idea/list.html.twig', [
            'ideas' => $ideas
        ]);
    }

    /**
     * @Route("/idea/new/{member_id}", name="idea_new", requirements={"member_id"="\d+"})
     */
    public function newAction(Request $request, SessionInterface $session, $member_id = null)
    {
        $em = $this->getDoctrine()->getManager();
        $idea = new Idea();

        if (is_null($member_id)) {
            $member = $this->getUser();
            $breadcrumb = [$this->generateUrl('idea_list') => 'Mes idées', '' => 'Ajouter une idée'];
        } else {
            $member = $this->getDoctrine()->getRepository(Member::class)->find($member_id);
            $breadcrumb = [$this->generateUrl('member_list') => 'Membres', '' => 'Ajouter une idée pour ' . $member->getFirstname()];
        }

        $form = $this->createForm(IdeaType::class, $idea);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $idea = $form->getData();

            // Ajoute une idée pour un autre
            if (! is_null($member_id)) {
                $idea->setMemberAdding($this->getUser());
            }

            $family = $this->getDoctrine()->getRepository(Family::class)->find($session->get('family')->getId());

            $idea->setState(0);
            $idea->setArchived(0);
            $idea->setMember($member);
            $idea->setFamily($family);
            $em->persist($idea);
            $em->flush();

            // todo : send email
//            $this->sendEmailInsert($idea, $request);


            if (is_null($member_id)) {
                // Si l'idée est pour le membre connecté
                $this->addFlash('success', 'L\'idée <b>' . $idea->getTitle() . '</b> a bien été ajoutée à votre liste.');
                return $this->redirectToRoute('idea_list');
            } else {
                // Si l'idée est pour un autre membre
                $this->addFlash('success', 'L\'idée <b>' . $idea->getTitle() . '</b> a bien été ajoutée à la liste de ' . $member->getFirstname() . '.');
                return $this->redirectToRoute('member_show', ['id' => $member_id]);
            }
        }
        return $this->render('idea/new.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb,
            'member' => $member
        ]);
    }

    /**
     * @Route("/idea/{id}/show", methods={"GET"}, name="idea_show", requirements={"id"="\d+"})
     */
    public function showAction(Idea $idea)
    {
        if (! $idea) {
            throw $this->createNotFoundException('Aucune idée trouvée pour l\'identifiant : ' . $idea->getId());
        }

        // todo: checkAccess
//        if (! $this->checkAccessShow($idee)) {
//            return $this->redirectToRoute('idea_list');
//        }

        $comment = new Comment();
        $formComment = $this->createForm(CommentType::class, $comment, [
            'action' => $this->generateUrl('comment_new', ['idea_id' => $idea->getId()])
        ]);

        $member = $idea->getMember();
        return $this->render('idea/show.html.twig', [
            'idea' => $idea,
            'breadcrumb' => [
                $this->generateUrl('member_list') => 'Membres',
                $this->generateUrl('member_show', ['id' => $member->getId()]) => $member->getFirstname(),
                '' => $idea->getTitle()
            ],
            'formComment' => $formComment->createView()
        ]);
    }

    /**
     * @Route("/idea/{id}/update", name="idea_update", requirements={"id"="\d+"})
     */
    public function updateAction(Request $request, Idea $idea)
    {
        if (! $idea) {
            throw $this->createNotFoundException('Aucune idée trouvée pour l\'identifiant : ' . $idea->getId());
        }

        // todo: checkAccess
//        if (! $this->checkAccessUpdate($idea)) {
//            return $this->redirectToRoute('idea_list');
//        }

        $form = $this->createForm(IdeaType::class, $idea);

        if ($idea->getComments()) {
            $comment = new Comment();
            $formComment = $this->createForm(CommentType::class, $comment, [
                'action' => $this->generateUrl('comment_new', ['idea_id' => $idea->getId()])
            ]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            // todo : envoi d'un mail
            // Envoi d'un mail de confirmation
//            $this->sendEmailUpdate($idea);

            $this->addFlash('success', 'Les modifications ont bien été prises en compte.');
            return $this->redirectToRoute('idea_list');
        }

        $member = $idea->getMember();
        if ($member->getId() === $this->getUser()->getId()) {
            $breadcrum = [
                $this->generateUrl('idea_list') => 'Mes idées',
                '' => $idea->getTitle()
            ];
        } else {
            $breadcrum = [
                $this->generateUrl('member_list') => 'Membres',
                $this->generateUrl('member_show', ['id' => $member->getId()]) => $member->getFirstname(),
                '' => $idea->getTitle()
            ];
        }

        return $this->render('idea/update.html.twig', [
            'form' => $form->createView(),
            'formComment' => $formComment->createView(),
            'breadcrumb' => $breadcrum,
            'idea' => $idea
        ]);
    }

    /**
     * @Route("/idea/{id}/archive", methods={"GET"}, name="idea_archive", requirements={"id"="\d+"})
     */
    public function archiveAction(Idea $idea)
    {
        if (! $idea) {
            throw $this->createNotFoundException('Aucune idée trouvée pour l\'identifiant : ' . $idea->getId());
        }

        $idea->setArchived(true);
        $em = $this->getDoctrine()->getManager();
        $em->persist($idea);
        $em->flush();

        $this->addFlash('success', 'L\'idée <b>' . $idea->getTitle() . '</b> a bien été archivée.');

        return $this->redirectToRoute('idea_archived_list');
    }

    /**
     * @Route("/idea/{id}/unarchive", methods={"GET"}, name="idea_unarchive", requirements={"id"="\d+"})
     */
    public function unarchiveAction(Idea $idea)
    {
        if (! $idea) {
            throw $this->createNotFoundException('Aucune idée trouvée pour l\'identifiant : ' . $idea->getId());
        }

        $idea->setArchived(false);
        $em = $this->getDoctrine()->getManager();
        $em->persist($idea);
        $em->flush();

        $this->addFlash('success', 'L\'idée <b>' . $idea->getTitle() . '</b> fait de nouveau partie de vos idées en cours.');

        return $this->redirectToRoute('idea_list');
    }

    /**
     * @Route("/idea/archived", methods={"GET"}, name="idea_archived_list")
     */
    public function listArchivedAction()
    {
        $ideas = $this->getDoctrine()
            ->getRepository(Idea::class)
            ->findBy(
                [
                    'member' => $this->getUser(),
                    'member_adding' => null,
                    'archived' => true
                ],
                ['id' => 'DESC']
            );

        return $this->render('idea/list.html.twig', [
            'ideas' => $ideas,
            'archived' => true
        ]);
    }

    /**
     * @Route("/idea/{id}/delete", methods={"GET"}, name="idea_delete", requirements={"id"="\d+"})
     */
    public function deleteAction(Idea $idea)
    {
        if (! $idea) {
            throw $this->createNotFoundException('Aucune idée trouvé pour l\'identifiant : ' . $idea->getId());
        }

        // todo : checkAccess
//        if (! $this->checkAccessDelete($idea)) {
//            return $this->redirectToRoute('idea_list');
//        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($idea);
        $em->flush();

        $this->addFlash('success', 'L\'idée a bien été supprimée.');

        if (is_null($idea->getMemberAdding())) {
            // Si le membre à supprimé une de ses idées
            return $this->redirectToRoute('idea_list');
        } else {
            // Si le membre a supprimé l'idée d'un autre membre
            return $this->redirectToRoute('member_show', ['id' => $idea->getMember()->getId()]);
        }
    }

    /**
     * @Route("/idea/{id}/{member_id}/state_update_to_take", methods={"GET"}, name="idea_state_update_to_take", requirements={"id"="\d+"})
     */
    public function stateUpdateToTakeAction($id, $member_id) {
        $em = $this->getDoctrine()->getManager();
        $idea = $em->getRepository(Idea::class)->find($id);

        if (! $idea) {
            throw $this->createNotFoundException('Aucune idée trouvée pour l\'identifiant : ' . $id);
        }

        $idea->setState(1);
        $idea->setMemberTaking($this->getUser());
        $em->persist($idea);
        $em->flush();

        return $this->redirectToRoute('member_show', ['id' => $member_id]);
    }

    /**
     * @Route("/idea/{id}/{member_id}/state_cancel_to_take", methods={"GET"}, name="idea_state_cancel_to_take", requirements={"id"="\d+"})
     */
    public function stateCancelToTakeAction($id, $member_id) {
        $em = $this->getDoctrine()->getManager();
        $idea = $em->getRepository(Idea::class)->find($id);

        if (! $idea) {
            throw $this->createNotFoundException('Aucune idée trouvée pour l\'identifiant : ' . $id);
        }

        $idea->setState(0);
        $idea->setMemberTaking(null);
        $em->persist($idea);
        $em->flush();

        return $this->redirectToRoute('member_show', ['id' => $member_id]);
    }

    /**
     * @Route("/idea/{id}/{member_id}/state_update_taken", methods={"GET"}, name="idea_state_update_taken", requirements={"id"="\d+"})
     */
    public function stateUpdateTakenAction($id, $member_id) {
        $em = $this->getDoctrine()->getManager();
        $idea = $em->getRepository(Idea::class)->find($id);

        if (! $idea) {
            throw $this->createNotFoundException('Aucune idée trouvée pour l\'identifiant : ' . $id);
        }

        $idea->setState(2);
        $idea->setMemberTaking($this->getUser());
        $em->persist($idea);
        $em->flush();

        return $this->redirectToRoute('member_show', ['id' => $member_id]);
    }
}
