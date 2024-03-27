<?php

namespace App\Controller;

use App\Entity\Family;
use App\Entity\Member;
use App\Form\FamilyJoinType;
use App\Form\FamilyType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class FamilyController extends AbstractController
{
    const HASH_PRIVATE_KEY = '5^Zu}_4eZG$Bee8eRvKD6pY#2nZtS=8r9?[{VW4&!2~7T(X3{7euaG]w8y49';

    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @Route("/family", name="family_list")
     */
    public function listAction()
    {
        $families = $this->getDoctrine()->getRepository(Family::class)->findByUser($this->getUser()->getId());

        return $this->render('family/list.html.twig', [
            'families' => $families
        ]);
    }

    /**
     * @Route("/family/new", name="family_new")
     */
    public function newAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $family = new Family();

        $form = $this->createForm(FamilyType::class, $family);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $family = $form->getData();

            $family->setMember($this->getUser());
            $family->setCode($this->generateUniqueCode());
            $family->addMember($this->getUser());

            $hash = hash('sha512', str_shuffle(self::HASH_PRIVATE_KEY . $family->getCode() . time()));
            $family->setHash($hash);

            $em->persist($family);
            $em->flush();

            $this->session->set('family', $family);

            $this->addFlash('success', 'La famille <b>' . $family->getName() . '</b> a bien été créée.');

            return $this->redirectToRoute('member_list');
        }
        return $this->render('family/new.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => [$this->generateUrl('family_list') => 'Mes familles', '' => 'Nouvelle famille']
        ]);
    }

    /**
     * @Route("/family/{id}/update", name="family_update", requirements={"id"="\d+"})
     */
    public function updateAction(Request $request, Family $family)
    {
        if (! $family) {
            throw $this->createNotFoundException('Aucune famille trouvée pour l\'identifiant : ' . $family->getId());
        }

        $form = $this->createForm(FamilyType::class, $family);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            // todo : envoi d'un mail
            // Envoi d'un mail de confirmation
//            $this->sendEmailUpdate($family);

            $this->addFlash('success', 'Les modifications ont bien été prises en compte.');
            return $this->redirectToRoute('family_list');
        }

        return $this->render('family/update.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => [$this->generateUrl('family_list') => 'Mes familles', '' => $family->getName()],
            'family' => $family
        ]);
    }

    /**
     * @Route("/family/{id}/delete", methods={"GET"}, name="family_delete", requirements={"id"="\d+"})
     */
    public function deleteAction(Family $family, SessionInterface $session)
    {
        if (! $family) {
            throw $this->createNotFoundException('Aucune famille trouvée pour l\'identifiant.');
        }

        // todo : checkAccess
//        if (! $this->checkAccessDelete($family)) {
//            return $this->redirectToRoute('idea_list');
//        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($family);
        $em->flush();

        $member = $this->getDoctrine()->getRepository(Member::class)->find($this->getUser()->getId());
        $userFamilies = $member->getFamilies();

        if ($userFamilies[0]) {
            $session->set('family', $userFamilies[0]);
        }

        $this->addFlash('success', 'La famille <b>' . $family->getName() . '</b> a bien été supprimée.');

        return $this->redirectToRoute('family_list');
    }

    /**
     * @Route("/family/join", name="family_join")
     */
    public function joinAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(FamilyJoinType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $code = $form->getData()['code'];

            $alreadyInFamily = $this->getDoctrine()->getRepository(Family::class)->findByCodeAndUser($code, $this->getUser()->getId());
            if ($alreadyInFamily) {
                $this->addFlash('warning', 'Vous faites déjà partie de la famille <b>' . $alreadyInFamily[0]['name'] . '</b>.');
                return $this->redirectToRoute('family_join');
            }

            $family = $this->getDoctrine()->getRepository(Family::class)->findOneBy(['code' => $form->getData()['code']]);

            if ($family) {
                $family->addMember($this->getUser());
                $em->persist($family);
                $em->flush();

                $this->session->set('family', $family);

                $this->addFlash('success', 'Vous faites maintenant partie de la famille <b>' . $family->getName() . '</b>.');

                return $this->redirectToRoute('member_list');
            } else {
                $this->addFlash('warning', 'Aucune famille correspondant au code <b>' . $form->getData()['code'] . '</b>.');

                return $this->redirectToRoute('family_join');
            }

        }
        return $this->render('family/join.html.twig', [
            'form' => $form->createView(),
            'breadcrumb' => [$this->generateUrl('family_list') => 'Mes familles', '' => 'Rejoindre une famille']
        ]);
    }

    /**
     * @Route("/family/join/{code}", name="family_join_code")
     */
    public function joinHashAction($code)
    {
        if (! $this->getUser()) {
            $this->session->set('family_code', $code);
            return $this->redirectToRoute('home');
        }

        if (strlen($code) !== 8) {
            throw $this->createNotFoundException('Paramètre incorrecte');
        }

        $family = $this->getDoctrine()->getRepository(Family::class)->findOneBy(['code' => $code]);

        if (! $family) {
            throw $this->createNotFoundException('Aucune famille correspondante');
        }

        if ($family->getMembers()->contains($this->getUser())) {
            $this->addFlash('success', 'Vous faites déjà partie de la famille <b>' . $family->getName() . '</b>.');
        } else {
            $em = $this->getDoctrine()->getManager();
            $family->addMember($this->getUser());
            $em->persist($family);
            $em->flush();

            $this->addFlash('success', 'Vous faites maintenant partie de la famille <b>' . $family->getName() . '</b>.');
        }

        $this->session->set('family', $family);

        return $this->redirectToRoute('member_list');
    }

    /**
     * @Route("/family/{id}/leave", name="family_leave")
     */
    public function leaveAction(Family $family, SessionInterface $session)
    {
        if (! $family) {
            throw $this->createNotFoundException('Aucune famille trouvée pour l\'identifiant.');
        }

        // todo : checkAccess
//        if (! $this->checkAccessDelete($family)) {
//            return $this->redirectToRoute('idea_list');
//        }

        $em = $this->getDoctrine()->getManager();
        $family->removeMember($this->getUser());
        $em->persist($family);
        $em->flush();

        $session->remove('family');

        $member = $this->getDoctrine()->getRepository(Member::class)->find($this->getUser()->getId());
        $userFamilies = $member->getFamilies();

        if ($userFamilies[0]) {
            $session->set('family', $userFamilies[0]);
        }

        $this->addFlash('success', 'Vous ne faites plus partie de la famille <b>' . $family->getName() . '</b>.');

        return $this->redirectToRoute('family_list');
    }

    /**
     * @Route("/family/{id}/connect", methods={"GET"}, name="family_connect", requirements={"id"="\d+"})
     */
    public function connectAction(Family $family)
    {
        $this->session->set('family', $family);

        return $this->redirectToRoute('member_list');
    }

    private function generateUniqueCode()
    {
        $em = $this->getDoctrine()->getRepository(Family::class);
        $chars = 'ABCDEFGHIJKLMNPQRSTUVWXYZ';

        do {
            $result = substr(str_shuffle($chars), 0, 4) . mt_rand(0, 9) . substr(str_shuffle($chars), 0, 3);
        } while ($em->findOneByCode($result));

        return $result;
    }
}
