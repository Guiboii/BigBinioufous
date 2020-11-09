<?php

namespace App\Controller;

use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DeskController extends AbstractController
{
    /**
     * @Route("/desk", name="desk")
     */
    public function index(EntityManagerInterface $manager, RoleRepository $repo)
    {
        $roles = $repo->findAll($manager, $repo);

        return $this->render('desk/index.html.twig', [
            'roles' => $roles
        ]);
    }
}
