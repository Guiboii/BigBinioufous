<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Form\EditUserType;
use App\Form\ValidRoleType;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    #[Route('/admin/valid', name: 'valid')]
    public function index(EntityManagerInterface $manager, UserRepository $repo): Response
    {
        $unvalids = $repo->findUnvalids($manager, $repo);

        return $this->render('admin/unvalids.html.twig', [
            'unvalids' => $unvalids,
            // 'binioufous' => $binioufous,
            // 'admins' => $admins,
            // 'members' => $members,
            // 'users' => $users
        ]);
    }

    /**
     * Ajoute le rôle d'admin à un utilisateur. Auparavant un lien vers une
     * page de confirmation à part (formulaire sans aucun champ, juste un
     * 2e bouton "Faire de cet utilisateur·rice un·e administrateur·rice" à
     * recliquer) : 2 clics pour rien, retour utilisatrice du 2026-08-11.
     * Action directe en un clic désormais, même pattern CSRF que
     * refuseUser/removeUserRole.
     */
    #[Route('/admin/setadmin/{slug}', name: 'create_admin', methods: ['POST'])]
    public function addAdminRole(#[MapEntity(mapping: ['slug' => 'slug'])] User $user, EntityManagerInterface $manager, RoleRepository $repo, Request $request): Response
    {
        if ($this->isCsrfTokenValid('create_admin'.$user->getId(), $request->request->get('_token'))) {
            $user->addRole($repo->findOneByTitle('ROLE_ADMIN'));
            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                'success',
                'Rôle ajouté'
            );
        }

        return $this->redirectToRoute('user_show', ['slug' => $user->getSlug()]);
    }

    /**
     * Ajoute le rôle de comptable à un utilisateur. Même simplification
     * que addAdminRole ci-dessus.
     */
    #[Route('/admin/setaccountant/{slug}', name: 'create_accountant', methods: ['POST'])]
    public function addAccountantRole(#[MapEntity(mapping: ['slug' => 'slug'])] User $user, EntityManagerInterface $manager, RoleRepository $repo, Request $request): Response
    {
        if ($this->isCsrfTokenValid('create_accountant'.$user->getId(), $request->request->get('_token'))) {
            $user->addRole($repo->findOneByTitle('ROLE_COMPTA'));
            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                'success',
                'Rôle ajouté'
            );
        }

        return $this->redirectToRoute('user_show', ['slug' => $user->getSlug()]);
    }

    /**
     * Ajoute le rôle binioufous à un utilisateur. Même pattern que
     * addAdminRole/addAccountantRole ci-dessus.
     */
    #[Route('/admin/setbinioufous/{slug}', name: 'create_binioufous', methods: ['POST'])]
    public function addBinioufousRole(#[MapEntity(mapping: ['slug' => 'slug'])] User $user, EntityManagerInterface $manager, RoleRepository $repo, Request $request): Response
    {
        if ($this->isCsrfTokenValid('create_binioufous'.$user->getId(), $request->request->get('_token'))) {
            $user->addRole($repo->findOneByTitle('ROLE_BINIOUFOUS'));
            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                'success',
                'Rôle ajouté'
            );
        }

        return $this->redirectToRoute('user_show', ['slug' => $user->getSlug()]);
    }

    /**
     * Permet d'afficher un utilisateur.
     */
    #[Route('/admin/user/{slug}', name: 'user_show')]
    public function showUser(#[MapEntity(mapping: ['slug' => 'slug'])] User $user, Request $request, EntityManagerInterface $manager, RoleRepository $repo)
    {
        $userRoles = $user->getRoles();
        $roles = $repo->findByTitle($userRoles);

        $form = $this->createForm(EditUserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                'success', 'Profile saved'
            );
        }

        return $this->render('admin/user/show.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Permet de valider l'inscription : attribue le rôle correspondant au
     * souhait (wish) choisi à l'inscription et marque le compte comme
     * validé. Les deux se produisent ensemble au submit, pas de case à
     * cocher séparée (cf. ValidRoleType, ancien champ retiré le 2026-08-11 :
     * ne gouvernait que $validation, jamais l'attribution du rôle elle-même,
     * source de confusion).
     *
     * @return Request
     */
    #[Route('/admin/{wish}/{slug}/valid', name: 'user_valid')]
    public function validUser(EntityManagerInterface $manager, #[MapEntity(mapping: ['slug' => 'slug'])] User $user, RoleRepository $repo, Request $request)
    {
        $wish = $user->getWish();
        $role = $repo->findOneByDescription($wish);

        $form = $this->createForm(ValidRoleType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->addRole($role);
            $user->setValidation(true);
            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                'success',
                'Utilisateur accepté'
            );

            return $this->redirectToRoute('valid');
        }

        return $this->render('admin/user/valid.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Permet de refuser une inscription en attente (supprime le compte,
     * il n'a jamais été validé). Même pattern CSRF/method-override que
     * EventController::delete.
     */
    #[Route('/admin/user/{slug}/refuse', name: 'user_refuse', methods: ['DELETE'])]
    public function refuseUser(#[MapEntity(mapping: ['slug' => 'slug'])] User $user, EntityManagerInterface $manager, Request $request): Response
    {
        if ($this->isCsrfTokenValid('refuse'.$user->getId(), $request->request->get('_token'))) {
            $manager->remove($user);
            $manager->flush();

            $this->addFlash(
                'success',
                'Inscription refusée'
            );
        }

        return $this->redirectToRoute('valid');
    }

    /**
     * Retire un rôle à un utilisateur (bouton "poubelle" sur les pastilles
     * de rôle de admin/user/show.html.twig, jusqu'ici sans action réelle
     * derrière : type="button" sans route ni CSRF). Même pattern que
     * refuseUser/EventController::delete.
     */
    #[Route('/admin/user/{slug}/role/{roleId}', name: 'user_remove_role', methods: ['DELETE'])]
    public function removeUserRole(#[MapEntity(mapping: ['slug' => 'slug'])] User $user, #[MapEntity(mapping: ['roleId' => 'id'])] Role $role, EntityManagerInterface $manager, Request $request): Response
    {
        if ($this->isCsrfTokenValid('remove_role'.$user->getId().$role->getId(), $request->request->get('_token'))) {
            $user->removeRole($role);
            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                'success',
                'Rôle retiré'
            );
        }

        return $this->redirectToRoute('user_show', ['slug' => $user->getSlug()]);
    }
}
