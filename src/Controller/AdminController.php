<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Form\AccountType;
use App\Form\AddAdminType;
use App\Form\EditUserType;
use App\Form\ValidRoleType;
use App\Form\AddAccountantType;
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
     * @Route("/admin/valid", name="valid")
     */
    public function index(EntityManagerInterface $manager, UserRepository $repo): Response
    {
        $unvalids = $repo->findUnvalids($manager, $repo);

        return $this->render('admin/unvalids.html.twig', [
            'unvalids' => $unvalids,
            //'binioufous' => $binioufous,
            //'admins' => $admins,
            //'members' => $members,
            //'users' => $users
        ]);
    }
   
    /**
     * Ajoute le rôle d'admin à un utilisateur
     *
     * @Route("/admin/setadmin/{slug}", name="create_admin")
     */

    public function addAdminRole(User $user, EntityManagerInterface $manager, RoleRepository $repo, Request $request)
    {
        $roles = $repo->findAll();
    
        $form = $this->createForm(AddAdminType::class, $user);
        
        $admin = $repo->findOneByTitle('ROLE_ADMIN');
        
        dump($admin);
        
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()) {
            $user->addRole($admin);
            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                'success',
                "Role add with success"
            );

            return $this->redirectToRoute('desk');
        }

                return $this->render('admin/user/addadmin.html.twig', [
            'user' => $user,
            'roles' => $roles,
            'form' => $form->createView()
        ]);
    }

    /**
     * Ajoute le rôle de comptable à un utilisateur
     *
     * @Route("/admin/setaccountant/{slug}", name="create_accountant")
     */

    public function addAccountantRole(User $user, EntityManagerInterface $manager, RoleRepository $repo, Request $request)
    {
        $roles = $repo->findAll();
    
        $form = $this->createForm(AddAccountantType::class, $user);
        
        $accountant = $repo->findOneByTitle('ROLE_COMPTA');
        
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()) {
            $user->addRole($accountant);
            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                'success',
                "Role add with success"
            );

            return $this->redirectToRoute('desk');
        }

                return $this->render('admin/user/addaccountant.html.twig', [
            'user' => $user,
            'roles' => $roles,
            'form' => $form->createView()
        ]);
    }

    /**
     * Permet d'afficher un utilisateur
     *
     * @Route("/admin/user/{slug}", name="user_show")
     */

    public function showUser(User $user, Request $request, EntityManagerInterface $manager, RoleRepository $repo){

        $userRoles = $user->getRoles();
        $roles = $repo->findByTitle($userRoles);

        $form = $this->createForm(EditUserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){

            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                'success', "Profile saved"
            );
        }

        return $this->render('admin/user/show.html.twig', [
            'form' => $form->createView(),
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

            return $this->redirectToRoute('valid');
        }

        return $this->render('admin/user/valid.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }


}
