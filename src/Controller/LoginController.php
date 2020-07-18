<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AccountType;
use App\Entity\PasswordUpdate;
use App\Form\RegistrationType;
use App\Form\PasswordUpdateType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class LoginController extends AbstractController
{
     /**
     * page to join
     *
     * @Route("/join", name="join")
     */
    public function index(AuthenticationUtils $utils, Request $request, EntityManagerInterface $manager, UserPasswordEncoderInterface $encoder)
    {
        $error = $utils->getLastAuthenticationError();
        $username = $utils->getLastUsername();

        return $this->render('join/index.html.twig', [
            'hasError' => $error !== null,
            'username' => $username
        ]);   
    }
    
    /**
     * to login
     * 
     * @Route("/login", name="login")
     * 
     * @return Response
     */
    public function login()
    {
        return $this->render('join/login.html.twig');
    }

    /**
     * to logout
     * 
     * @Route("/logout", name="logout")
     *
     * @return void
     */
    public function logout() {}

    /**
     * to register
     *
     * @Route("/register", name="register")
     * 
     * @return Response
     */
    public function register(Request $request, EntityManagerInterface $manager, UserPasswordEncoderInterface $encoder){
        $user = new User();

        $form = $this->createForm(RegistrationType::class, $user);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $hash = $encoder->encodePassword($user, $user->getHash());
            $user->setHash($hash);

            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
            'success', "Welcome ! Log you now, your account has been created"
            );


            return $this->redirectToRoute('join');
        }

        return $this->render('join/registration.html.twig', [
            'form' => $form->createView()
        ]);
    }
    /**
     * update profile
     * 
     * @Route("/account/profile", name="profile")
     *
     * @return Response
     */
    public function profile(Request $request, EntityManagerInterface $manager){

        $user = $this->getUser();
        $form = $this->createForm(AccountType::class, $user);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                'success', "Profile saved"
            );
        }

        return $this->render('join/profile.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Update the password
     *
     * @Route("/account/update-password", name="update-password")
     * 
     * @return void
     */
    public function updatePassword(Request $request, UserPasswordEncoderInterface $encoder, EntityManagerInterface $manager){
        $passwordUpdate = new PasswordUpdate();

        $user = $this->getUser();

        $form = $this->createForm(PasswordUpdateType::class, $passwordUpdate);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            if(!password_verify($passwordUpdate->getOldPassword(), $user->getHash())){

            } else {
                $newPassword = $passwordUpdate->getNewPassword();
                $hash = $encoder->encodePassword($user, $newPassword);

                $user->setHash($hash);

                $manager->persist($user);
                $manager->flush();

                $this->addFlash(
                    'success',
                    "Your password has been update"
                );

                return $this->redirectToRoute('profile');
            }
        }

        return $this->render('join/password.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
