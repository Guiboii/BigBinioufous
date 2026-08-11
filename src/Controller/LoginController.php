<?php

namespace App\Controller;

use App\Entity\PasswordUpdate;
use App\Entity\User;
use App\Form\AccountType;
use App\Form\PasswordUpdateType;
use App\Form\RegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\String\Slugger\SluggerInterface;

class LoginController extends AbstractController
{
    /**
     * page to join.
     */
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

    /**
     * /login redirects to /join: security.yaml's login_path/check_path already
     * point to the "join" route, /login is never used by the real auth flow.
     * join/login.html.twig used to be rendered here as a standalone document
     * (via popup.html.twig) but is now a fragment included inline in
     * join/index.html.twig, so it can no longer be rendered on its own.
     */
    #[Route('/login', name: 'login')]
    public function login(): Response
    {
        return $this->redirectToRoute('join');
    }

    /**
     * to logout.
     *
     * @return void
     */
    #[Route('/logout', name: 'logout')]
    public function logout()
    {
    }

    /**
     * to register.
     *
     * @return Response
     */
    #[Route('/register', name: 'register')]
    public function register(Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $encoder, SluggerInterface $slugger)
    {
        $user = new User();

        $form = $this->createForm(RegistrationType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // valid simple user automatically

            $validation = false;
            $userole = $form->get('wish')->getData();
            if ('Simple' == $userole) {
                $validation = true;
            }

            /** @var UploadedFile $brochureFile */
            $pictureFile = $form->get('picture')->getData();

            $newFilename = null;

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($pictureFile) {
                $originalFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$pictureFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $pictureFile->move(
                        $this->getParameter('pictures_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
            }

            $hash = $encoder->hashPassword($user, $user->getHash());
            $user->setHash($hash)
                    ->setValidation($validation)
                    ->setPicture($newFilename);

            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                'success', 'Welcome ! Log you now, your account has been created'
            );

            return $this->redirectToRoute('join');
        }

        return $this->render('join/registration.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * update profile.
     *
     * @return Response
     */
    #[Route('/desk/profile', name: 'profile')]
    public function profile(Request $request, EntityManagerInterface $manager, SluggerInterface $slugger)
    {
        $user = $this->getUser();
        $form = $this->createForm(AccountType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $brochureFile */
            $pictureFile = $form->get('picture')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($pictureFile) {
                $originalFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$pictureFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $pictureFile->move(
                        $this->getParameter('pictures_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
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
        ]);
    }

    /**
     * Update the password.
     *
     * @return void
     */
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
