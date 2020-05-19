<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class JoinController extends AbstractController
{
    /**
     * @Route("/join", name="join")
     */
    public function index(AuthenticationUtils $utils)
    {

        return $this->render('join/index.html.twig', [
            'controller_name' => 'JoinController',
        ]);
    }
}
