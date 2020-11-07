<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DeskController extends AbstractController
{
    /**
     * @Route("/desk", name="desk")
     */
    public function index()
    {
        return $this->render('desk/index.html.twig', [
            'controller_name' => 'DeskController',
        ]);
    }
}
