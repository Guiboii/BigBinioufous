<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Form\EditUserType;
use App\Form\ValidRoleType;
use App\Mailer\RegistrationMailer;
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
     * Bascule ROLE_BINIOUFOUS en un clic, directement depuis les listes desk
     * (templates/desk/lists/simples.html.twig et binioufous.html.twig)
     * plutôt que de passer par la fiche détaillée de chaque utilisateur·ice.
     * Remplace les boutons de promotion individuels (create_admin/
     * create_accountant/create_binioufous, gardés tels quels sur la fiche
     * admin/user/show.html.twig pour les rôles admin/comptable, hors scope
     * ici) pour ce cas précis : simplification des rôles décidée le
     * 2026-08-12 (cf. ROADMAP.md), ROLE_MEMBER n'ayant jamais débloqué de
     * permission propre dans le code (absent de security.yaml), seul
     * ROLE_BINIOUFOUS fait une vraie différence (accès aux partitions/voix).
     * ROLE_SIMPLE fusionné avec ROLE_USER (cf. ROADMAP.md "Rôles fusionnés",
     * 2026-08-12) : "pas membre" n'est plus un rôle à assigner, juste
     * l'absence de ROLE_BINIOUFOUS. ROLE_MEMBER supprimé le même jour (cf.
     * ROADMAP.md "Rôles legacy et implicite nettoyés", migration
     * Version20260812180000) : plus aucun moyen de l'attribuer
     * (create_member retiré le 2026-08-12) ni aucun compte ne pouvant
     * encore l'avoir.
     */
    #[Route('/admin/user/{slug}/toggle-membership', name: 'user_toggle_membership', methods: ['POST'])]
    public function toggleMembership(#[MapEntity(mapping: ['slug' => 'slug'])] User $user, EntityManagerInterface $manager, RoleRepository $repo, Request $request): Response
    {
        if ($this->isCsrfTokenValid('toggle_membership'.$user->getId(), $request->request->get('_token'))) {
            $binioufousRole = $repo->findOneByTitle('ROLE_BINIOUFOUS');

            if (\in_array('ROLE_BINIOUFOUS', $user->getRoles(), true)) {
                $user->removeRole($binioufousRole);
                $this->addFlash('success', $user->getFullName().' n\'est plus "Membre".');
            } else {
                $user->addRole($binioufousRole);
                $this->addFlash('success', $user->getFullName().' est maintenant "Membre".');
            }

            $manager->persist($user);
            $manager->flush();
        }

        return $this->redirectToRoute('desk');
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
     * Permet de valider l'inscription : marque le compte comme validé
     * (accès débloqué, cf. src/Security/UserChecker.php) et prévient la
     * personne par mail. N'attribue plus aucun rôle (avant : rôle déduit du
     * souhait "wish" choisi à l'inscription, champ retiré le 2026-08-12,
     * cf. ROADMAP.md "Facilitons l'inscription") : le rôle Membre/Pas
     * membre se décide après coup, indépendamment de cette validation, via
     * le toggle sur les listes desk (AdminController::toggleMembership()).
     * Le bug historique ("compte validé invisible dans toutes les listes
     * desk") qui avait fait ajouter un ROLE_SIMPLE de palier ici ne se pose
     * plus : ROLE_SIMPLE est fusionné avec ROLE_USER (cf. ROADMAP.md "Rôles
     * fusionnés", 2026-08-12), UserRepository::findSimples() traite
     * directement "validé, sans ROLE_BINIOUFOUS" sans avoir besoin d'un
     * rôle explicite.
     */
    #[Route('/admin/{slug}/valid', name: 'user_valid')]
    public function validUser(EntityManagerInterface $manager, #[MapEntity(mapping: ['slug' => 'slug'])] User $user, Request $request, RegistrationMailer $registrationMailer)
    {
        $form = $this->createForm(ValidRoleType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setValidation(true);

            $manager->persist($user);
            $manager->flush();

            $registrationMailer->sendValidated($user);

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
