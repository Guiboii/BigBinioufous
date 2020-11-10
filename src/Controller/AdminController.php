<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ValidRoleType;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
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
     * Permet de valider l'inscription
     * 
     * @Route("/admin/{wish}/{slug}/valid", name="user_valid")
     *
     * @return Request
     */
    public function validUser(EntityManagerInterface $manager, User $user, RoleRepository $repo, Request $request){

        $wish = $user->getWish();
        $role = $repo->findOneByDescription($wish);

        $form = $this->createForm(ValidRoleType::class, $user);
        
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()) {
            $user->addRole($role);
            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                'success',
                "Utilisateur accepté"
            );

            return $this->redirectToRoute('admin');
        }

        return $this->render('admin/user/valid.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }


}
