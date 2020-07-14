<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
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
        /**
         * login part
         */

        $error = $utils->getLastAuthenticationError();
        $username = $utils->getLastUsername();

        return $this->render('join/index.html.twig', [
            'hasError' => $error !== null,
            'username' => $username,
        ]);
        
    }
    
    /**
     * to login
     * 
     * @Route("/login", name="login")
     * 
     * @return Response
     */
    public function login(AuthenticationUtils $utils)
    {
        $error = $utils->getLastAuthenticationError();
        $username = $utils->getLastUsername();

        return $this->render('join/login.html.twig', [
            'hasError' => $error !== null,
            'username' => $username
        ]);
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
}
