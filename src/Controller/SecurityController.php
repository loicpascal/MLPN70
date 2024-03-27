<?php

namespace App\Controller;

use App\Entity\Member;
use App\Form\PasswordForgottenType;
use App\Service\Mail\MailSecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    const PASSWORD_HASH_PRIVATE_KEY = '37h=rNzJ%[V4?5rGd$6r75vRHp5}*Y*{V5.@U2msf4<eC7H4s2rF34z$RGw<';

    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
         if ($this->getUser()) {
             return $this->redirectToRoute('member_list');
         }

        return $this->render('home/index.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError()
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route("/passwordReset", name="password_reset")
     */
    public function passwordReset(Request $request, MailSecurityService $mailer)
    {
        $form = $this->createForm(PasswordForgottenType::class, null, [
            'action' => $this->generateUrl('password_forgotten')
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $member = $em->getRepository(Member::class)->findOneBy(['email' => $form->getData()['email']]);
            if ($member) {
                $this->sendEmailPasswordForgotten($member, $request);
            }
        }

        return $this->render('security/passwordForgotten.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/passwordForgotten", name="password_forgotten")
     */
    public function passwordForgotten(Request $request, MailSecurityService $mailer)
    {
        $form = $this->createForm(PasswordForgottenType::class, null, [
            'action' => $this->generateUrl('password_forgotten')
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $member = $em->getRepository(Member::class)->findOneBy(['email' => $form->getData()['email']]);
            if ($member) {
                $password_hash = hash('sha512', str_shuffle(self::PASSWORD_HASH_PRIVATE_KEY . $member->getEmail() . time()));
                $member->setPasswordForgottenHash($password_hash);
                $em->flush();

                // todo: envoyer le lien de mot de passe perdu
                $mailer->sendEmailPasswordForgotten($member, $request, $this->generateUrl('password_reset', ['hash' => $password_hash]));
                $this->addFlash('success', 'Un mail a été envoyé à l\'adresse "' . $form->getData()['email'] . '". Cliquez sur lien qu\'il contient pour redéfinir votre mot de passe.');
            }
        }

        return $this->render('security/passwordForgotten.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
