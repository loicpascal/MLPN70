<?php

namespace App\Controller;

use App\Entity\Idea;
use App\Entity\Member;
use App\Form\MemberInfosType;
use App\Form\MemberNotificationsType;
use App\Form\MemberPwdType;
use App\Form\MemberType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class MemberController extends AbstractController
{
    /**
     * @Route("/member", name="member_list")
     */
    public function listAction(SessionInterface $session)
    {
        // Tests faits à la connexion
        if ($code = $session->remove('family_code')) {
            return $this->redirectToRoute('family_join_code', ['code' => $code]);
        }

        if (! $session->get('family')) {
            $member = $this->getDoctrine()->getRepository(Member::class)->find($this->getUser()->getId());
            $userFamilies = $member->getFamilies();

            if ($userFamilies[0]) {
                $session->set('family', $userFamilies[0]);
            } else {
                return $this->redirectToRoute('family_list');
            }
        }

        $members = $this->getDoctrine()->getRepository(Member::class)->findByFamilyId($session->get('family')->getId());

        return $this->render('member/list.html.twig', [
            'controller_name' => 'MemberController',
            'members' => $members
        ]);
    }

    /**
     * @Route("/createAccount", name="create_account")
     */
    public function newAction(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('member_list');
        }

        $member = new Member();

        $form = $this->createForm(MemberType::class, $member);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            if ($em->getRepository(Member::class)->findOneBy(['email' => $member->getEmail()])) {
                $this->addFlash('warning', 'Cet identifiant est déjà pris.');
                return $this->redirectToRoute('createAccount');
            }

            $password = $passwordEncoder->encodePassword($member, $member->getPassword());
            $member->setPassword($password);
            $member->setRoles(['ROLE_USER']);
            $member->setIsActive(false);
            $member->setReceiveEmailNewComment(false);

            $em->persist($member);
            $em->flush();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('member/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/member/{id}", name="member_show", requirements={"id"="\d+"})
     */
    public function showAction(Member $member)
    {
        if (! $member) {
            throw $this->createNotFoundException('Aucun membre trouvé pour l\'identifiant : ' . $member->getId());
        } elseif ($member->getUsername() === $this->getUser()->getUsername()) {
            $this->addFlash('danger', 'Bien essayé !');
            return $this->redirectToRoute('member_list');
        }

        $ideas = $this->getDoctrine()
            ->getRepository(Idea::class)
            ->findBy(
                [
                    'member' => $member,
                    'archived' => false
                ],
                ['id' => 'DESC']
            );

        return $this->render('member/show.html.twig', [
            'member' => $member,
            'breadcrumb' => [$this->generateUrl('member_list') => 'Membres', '' => $member->getFirstname()],
            'ideas' => $ideas
        ]);
    }

    /**
     * @Route("/account", name="my_account")
     */
    public function updateAction()
    {
        $formInfos = $this->createForm(MemberInfosType::class, $this->getUser(), [
            'action' => $this->generateUrl('member_update_infos', ['id' => $this->getUser()->getId()])
        ]);
        $formNotifications = $this->createForm(MemberNotificationsType::class, $this->getUser(), [
            'action' => $this->generateUrl('member_update_notifications', ['id' => $this->getUser()->getId()])
        ]);
        $formPwd = $this->createForm(MemberPwdType::class, $this->getUser(), [
            'action' => $this->generateUrl('member_update_password', ['id' => $this->getUser()->getId()])
        ]);

        return $this->render('member/update.html.twig', [
            'member' => $this->getUser(),
            'formInfos' => $formInfos->createView(),
            'formNotifications' => $formNotifications->createView(),
            'formPwd' => $formPwd->createView()
        ]);
    }

    /**
     * @Route("/member/{id}/updateInfos", name="member_update_infos", requirements={"id"="\d+"})
     */
    public function updateInfosAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $member = $em->getRepository(Member::class)->find($id);

        if (! $member || intval($id) !== $this->getUser()->getId()) {
            return $this->redirectToRoute('member_list');
        }

        $formInfos = $this->createForm(MemberInfosType::class, $member, [
            'action' => $this->generateUrl('member_update_infos', ['id' => $id])
        ]);
        $formNotifications = $this->createForm(MemberNotificationsType::class, $member, [
            'action' => $this->generateUrl('member_update_notifications', ['id' => $member->getId()])
        ]);
        $formPwd = $this->createForm(MemberPwdType::class, $member, [
            'action' => $this->generateUrl('member_update_password', ['id' => $id])
        ]);

        $formInfos->handleRequest($request);

        if ($formInfos->isSubmitted() && $formInfos->isValid()) {
            $em->flush();

            $this->addFlash('successInfosUpdate', 'Vos informations ont été modifiées avec succès !');
            return $this->redirectToRoute('my_account', ['panel' => 'infos']);
        }

        return $this->render('member/update.html.twig', [
            'member' => $member,
            'formInfos' => $formInfos->createView(),
            'formNotifications' => $formNotifications->createView(),
            'formPwd' => $formPwd->createView()
        ]);
    }

    /**
     * @Route("/member/{id}/updateNotifications", name="member_update_notifications", requirements={"id"="\d+"})
     */
    public function updateNotificationsAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $member = $em->getRepository(Member::class)->find($id);

        if (! $member || intval($id) !== $this->getUser()->getId()) {
            return $this->redirectToRoute('member_list');
        }

        $formInfos = $this->createForm(MemberInfosType::class, $member, [
            'action' => $this->generateUrl('member_update_infos', ['id' => $id])
        ]);
        $formNotifications = $this->createForm(MemberNotificationsType::class, $member, [
            'action' => $this->generateUrl('member_update_notifications', ['id' => $member->getId()])
        ]);
        $formPwd = $this->createForm(MemberPwdType::class, $member, [
            'action' => $this->generateUrl('member_update_password', ['id' => $id])
        ]);

        $formNotifications->handleRequest($request);

        if ($formNotifications->isSubmitted() && $formNotifications->isValid()) {
            $em->flush();

            $this->addFlash('successNotifsUpdate', 'Vos informations ont été modifiées avec succès !');
            return $this->redirectToRoute('my_account', ['panel' => 'notifs']);
        }

        return $this->render('member/update.html.twig', [
            'member' => $member,
            'formInfos' => $formInfos->createView(),
            'formNotifications' => $formNotifications->createView(),
            'formPwd' => $formPwd->createView()
        ]);
    }

    /**
     * @Route("/member/{id}/updatePwd", name="member_update_password", requirements={"id"="\d+"})
     */
    public function updatePwdAction(Request $request, $id, UserPasswordEncoderInterface $passwordEncoder)
    {
        $em = $this->getDoctrine()->getManager();
        $member = $em->getRepository(Member::class)->find($id);

        if (! $member || intval($id) !== $this->getUser()->getId()) {
            return $this->redirectToRoute('member_list');
        }

        $formInfos = $this->createForm(MemberInfosType::class, $member, [
            'action' => $this->generateUrl('member_update_infos', ['id' => $id])
        ]);
        $formNotifications = $this->createForm(MemberNotificationsType::class, $member, [
            'action' => $this->generateUrl('member_update_notifications', ['id' => $member->getId()])
        ]);
        $formPwd = $this->createForm(MemberPwdType::class, $member, [
            'action' => $this->generateUrl('member_update_password', ['id' => $id])
        ]);

        $formPwd->handleRequest($request);

        if ($formPwd->isSubmitted() && $formPwd->isValid()) {
            if ($member->getPassword() === '') {
                $this->addFlash('dangerPwdUpdate', 'Votre mot de passe ne peut pas être vide.');
            } else {
                $password = $passwordEncoder->encodePassword($member, $member->getPassword());
                $member->setPassword($password);
                $em->flush();

                $this->addFlash('successPwdUpdate', 'Votre mot de passe a été modifié avec succès !');
            }
            return $this->redirectToRoute('my_account', ['panel' => 'password']);
        }

        return $this->render('member/update.html.twig', [
            'member' => $member,
            'formInfos' => $formInfos->createView(),
            'formNotifications' => $formNotifications->createView(),
            'formPwd' => $formPwd->createView()
        ]);
    }

    /**
     * @Route("/member/delete/{id}", name="member_delete")
     */
    public function deleteAction(SessionInterface $session, AuthorizationCheckerInterface $authChecker, $id = null)
    {
        $em = $this->getDoctrine()->getManager();

        if (is_null($id)) {
            // On souhaite supprimer l'utilisateur connecté

            $member = $this->getUser();
            if (count($member->getIdees())) {
                $this->addFlash('danger', 'Vous avez des idées. Supprimez d\'abord vos idées pour supprimer votre compte.');
                return $this->redirectToRoute('my_account');
            }

            $this->get('security.token_storage')->setToken(null);
            $session->invalidate();
            $em->remove($member);
            $em->flush();
            $this->addFlash('success', 'Votre compte a été supprimé avec succès.');
            return $this->redirectToRoute('login');

        } else {
            // On souhaite supprimer l'utilisateur $id

            // Si l'utilisateur n'est pas ADMIN
            if (! $authChecker->isGranted('ROLE_ADMIN')) {
                $this->addFlash('danger', 'Vous n\'avez pas les droits suffisants pour effectuer cette opération.');
                return $this->redirectToRoute('member_list');
            }

            $member = $em->getRepository(Member::class)->find($id);

            if (count($member->getIdees())) {
                $this->addFlash('danger', 'Ce membre a des idées. Supprimez d\'abord ses idées pour supprimer son compte.');
            } else {
                $this->addFlash('success', 'Le compte de ' . $member->getFullname() . ' a été supprimé avec succès.');
                $em->remove($member);
                $em->flush();
            }
            return $this->redirectToRoute('member_list');
        }
    }

    /**
     * @Route("/member/shopping-list", name="member_shopping_list")
     */
    public function shoppingListAction()
    {
        $ideas = $this->getDoctrine()->getRepository(Idea::class)->findAllByUserTaking($this->getUser()->getId(), 0);

        return $this->render('member/shoppingList.html.twig', [
            'ideas' => $ideas
        ]);
    }

    /**
     * @Route("/member/deleteAllIdeas", name="member_ideas_delete")
     */
    public function deleteAllIdeasAction()
    {
        $em = $this->getDoctrine()->getManager();
        $em->getRepository(Member::class)->deleteAllOngoingIdees($this->getUser());

        $this->addFlash('success', 'Vos idées ont bien été supprimées !');
        return $this->redirectToRoute('idea_list');
    }
}
