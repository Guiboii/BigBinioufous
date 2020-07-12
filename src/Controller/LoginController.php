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
     * page to join
     *
     * @Route("/join", name="join")
     */
    public function index(AuthenticationUtils $utils)
    {
        $user = new User();
        $error = $utils->getLastAuthenticationError();
        $username = $utils->getLastUsername();
        $form = $this->createForm(RegistrationType::class, $user);

        return $this->render('join/index.html.twig', [
            'hasError' => $error !== null,
            'username' => $username,
            'form' => $form->createView()
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
    public function register(){
        $user = new User();

        $form = $this->createForm(RegistrationType::class, $user);

        return $this->render('join/registration.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
