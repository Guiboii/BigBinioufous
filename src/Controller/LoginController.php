<?php

namespace App\Controller;

use App\Entity\PasswordUpdate;
use App\Entity\User;
use App\Form\AccountType;
use App\Form\PasswordUpdateType;
use App\Form\RegistrationType;
use App\Mailer\RegistrationMailer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\String\Slugger\SluggerInterface;

// Authentication: login page, registration, password change, profile picture upload.
class LoginController extends AbstractController
{
    #[Route('/join', name: 'join')]
    public function index(AuthenticationUtils $utils, Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $encoder)
    {
        $error = $utils->getLastAuthenticationError();
        $username = $utils->getLastUsername();

        return $this->render('join/index.html.twig', [
            'hasError' => null !== $error,
            'username' => $username,
        ]);
    }

    // security.yaml's login_path/check_path point to "join", so /login is never used by the real auth flow and just redirects there.
    #[Route('/login', name: 'login')]
    public function login(): Response
    {
        return $this->redirectToRoute('join');
    }

    // Intercepted by the security firewall before this body ever runs.
    #[Route('/logout', name: 'logout')]
    public function logout()
    {
    }

    /**
     * Registration only asks for nickname/email/password. The account is never auto-validated; it always waits on manual admin approval. Auto-logs the account in right after signup, then redirects to the profile page to complete it.
     *
     * Security::login() needs the authenticator name spelled out ('form_login') because the main firewall exposes two (form_login + scheb/2fa-bundle's two_factor); omitting it throws "Too many authenticators". No 2FA interference here since a brand new account never has a TOTP secret yet.
     */
    #[Route('/register', name: 'register')]
    public function register(Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $encoder, MailerInterface $mailer, LoggerInterface $logger, #[Autowire(param: 'admin_notification_email')] string $adminNotificationEmail, RegistrationMailer $registrationMailer, Security $security)
    {
        $user = new User();

        $form = $this->createForm(RegistrationType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hash = $encoder->hashPassword($user, $user->getHash());
            $user->setHash($hash)->setValidation(false);

            $manager->persist($user);
            $manager->flush();

            $this->notifyAdminsOfNewRegistration($mailer, $logger, $adminNotificationEmail, $user);
            $registrationMailer->sendPendingValidation($user);

            $security->login($user, 'form_login', 'main');

            $this->addFlash(
                'success',
                'Ton compte a été créé ! Il est en cours de vérification par un·e admin, mais tu peux déjà compléter ton profil.'
            );

            return $this->redirectToRoute('profile');
        }

        return $this->render('join/registration.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // MAILER_DSN isn't configured in prod yet, so nothing actually sends for now. Errors are caught and logged rather than left to bubble up: this must never fail the registration itself, already saved to the database at this point.
    private function notifyAdminsOfNewRegistration(MailerInterface $mailer, LoggerInterface $logger, string $adminNotificationEmail, User $user): void
    {
        try {
            $email = (new Email())
                ->to($adminNotificationEmail)
                ->subject('Nouvelle inscription en attente : '.$user->getNickname())
                ->text(sprintf(
                    "%s (%s) vient de s'inscrire et attend une validation.\n\nValider ou refuser : %s",
                    $user->getNickname(),
                    $user->getEmail(),
                    $this->generateUrl('valid', [], UrlGeneratorInterface::ABSOLUTE_URL)
                ));

            $mailer->send($email);
        } catch (\Throwable $e) {
            $logger->error('Échec de l\'envoi du mail de notification d\'inscription : '.$e->getMessage(), ['exception' => $e]);
        }
    }

    #[Route('/desk/profile', name: 'profile')]
    public function profile(Request $request, EntityManagerInterface $manager, SluggerInterface $slugger)
    {
        $user = $this->getUser();
        $form = $this->createForm(AccountType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pictureFile = $form->get('picture')->getData();

            // The picture field is optional, only process a file when one was actually uploaded.
            if ($pictureFile) {
                $originalFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$pictureFile->guessExtension();

                try {
                    $pictureFile->move(
                        $this->getParameter('pictures_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                }

                $user->setPicture($newFilename);
            }

            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                'success', 'Profile saved'
            );
        }

        return $this->render('desk/profile.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/desk/update-password', name: 'update-password')]
    public function updatePassword(Request $request, UserPasswordHasherInterface $encoder, EntityManagerInterface $manager)
    {
        $passwordUpdate = new PasswordUpdate();

        $user = $this->getUser();

        $form = $this->createForm(PasswordUpdateType::class, $passwordUpdate);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!password_verify($passwordUpdate->getOldPassword(), $user->getHash())) {
            } else {
                $newPassword = $passwordUpdate->getNewPassword();
                $hash = $encoder->hashPassword($user, $newPassword);

                $user->setHash($hash);

                $manager->persist($user);
                $manager->flush();

                $this->addFlash(
                    'success',
                    'Your password has been update'
                );

                return $this->redirectToRoute('profile');
            }
        }

        return $this->render('desk/password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
