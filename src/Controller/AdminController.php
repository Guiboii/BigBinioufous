<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin", name="admin")
     */
    public function index(EntityManagerInterface $manager, UserRepository $repo): Response
    {
        $unvalids = $repo->findUnvalids($manager, $repo);

        return $this->render('admin/index.html.twig', [
            'unvalids' => $unvalids,
            //'binioufous' => $binioufous,
            //'admins' => $admins,
            //'members' => $members,
            //'users' => $users
        ]);
    }

    /**
     * @Route("/admin/compta", name="accountant")
     */
    public function compta(): Response
    {
        return $this->render('admin/compta.html.twig');
    }

   
    /**
     * Permet d'afficher un utilisateur
     *
     * @Route("/admin/users/{slug}", name="user_show")
     */

    public function showUser(User $user, EntityManagerInterface $manager)
    {
        $roles = $user->getUserRoles();

        dump($roles);
                return $this->render('admin/user/show.html.twig', [
            'user' => $user,
            'roles' => $roles
        ]);
    }

    /**
     * Permet de valider la demande d'un utilisateur
     *
     * @Route("/admin/{wish}/{slug}/valid", name="user_valid")
     */

    public function validUser(User $user, EntityManagerInterface $manager)
    {
        $roles = $user->getRoles();

            return $this->render('admin/user/show.html.twig', [
            'user' => $user,
            'roles' => $roles
        ]);
    }

}
