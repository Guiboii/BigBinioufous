<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
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

        return $this->render('login/login.html.twig', [
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
    public function register(){
        $user = new User();

        $form = $this->createForm(RegistrationType::class, $user);

        return $this->render('login/registration.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
