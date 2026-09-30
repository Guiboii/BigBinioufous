<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Form\EditUserType;
use App\Mailer\RegistrationMailer;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Admin actions on user accounts: validation, role assignment, membership toggle.
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

    // Grants ROLE_ADMIN in one click (a plain CSRF-protected POST, no separate confirmation page).
    #[Route('/admin/setadmin/{slug}', name: 'create_admin', methods: ['POST'])]
    public function addAdminRole(#[MapEntity(mapping: ['slug' => 'slug'])] User $user, EntityManagerInterface $manager, RoleRepository $repo, Request $request, LoggerInterface $logger): Response
    {
        if ($this->isCsrfTokenValid('create_admin'.$user->getId(), $request->request->get('_token'))) {
            $user->addRole($repo->findOneByTitle('ROLE_ADMIN'));
            $manager->persist($user);
            $manager->flush();

            $logger->info('Rôle ROLE_ADMIN ajouté', ['target' => $user->getEmail(), 'by' => $this->getUser()?->getUserIdentifier()]);

            $this->addFlash(
                'success',
                'Rôle ajouté'
            );
        }

        return $this->redirectToRoute('user_show', ['slug' => $user->getSlug()]);
    }

    // Grants ROLE_COMPTA, same pattern as addAdminRole above.
    #[Route('/admin/setaccountant/{slug}', name: 'create_accountant', methods: ['POST'])]
    public function addAccountantRole(#[MapEntity(mapping: ['slug' => 'slug'])] User $user, EntityManagerInterface $manager, RoleRepository $repo, Request $request, LoggerInterface $logger): Response
    {
        if ($this->isCsrfTokenValid('create_accountant'.$user->getId(), $request->request->get('_token'))) {
            $user->addRole($repo->findOneByTitle('ROLE_COMPTA'));
            $manager->persist($user);
            $manager->flush();

            $logger->info('Rôle ROLE_COMPTA ajouté', ['target' => $user->getEmail(), 'by' => $this->getUser()?->getUserIdentifier()]);

            $this->addFlash(
                'success',
                'Rôle ajouté'
            );
        }

        return $this->redirectToRoute('user_show', ['slug' => $user->getSlug()]);
    }

    // Grants ROLE_BINIOUFOUS, same pattern as addAdminRole/addAccountantRole above.
    #[Route('/admin/setbinioufous/{slug}', name: 'create_binioufous', methods: ['POST'])]
    public function addBinioufousRole(#[MapEntity(mapping: ['slug' => 'slug'])] User $user, EntityManagerInterface $manager, RoleRepository $repo, Request $request, LoggerInterface $logger): Response
    {
        if ($this->isCsrfTokenValid('create_binioufous'.$user->getId(), $request->request->get('_token'))) {
            $user->addRole($repo->findOneByTitle('ROLE_BINIOUFOUS'));
            $manager->persist($user);
            $manager->flush();

            $logger->info('Rôle ROLE_BINIOUFOUS ajouté', ['target' => $user->getEmail(), 'by' => $this->getUser()?->getUserIdentifier()]);

            $this->addFlash(
                'success',
                'Rôle ajouté'
            );
        }

        return $this->redirectToRoute('user_show', ['slug' => $user->getSlug()]);
    }

    // Toggles ROLE_BINIOUFOUS in one click from the desk member lists, the only role with a real functional difference (access to sheet music/parts).
    #[Route('/admin/user/{slug}/toggle-membership', name: 'user_toggle_membership', methods: ['POST'])]
    public function toggleMembership(#[MapEntity(mapping: ['slug' => 'slug'])] User $user, EntityManagerInterface $manager, RoleRepository $repo, Request $request, LoggerInterface $logger): Response
    {
        if ($this->isCsrfTokenValid('toggle_membership'.$user->getId(), $request->request->get('_token'))) {
            $binioufousRole = $repo->findOneByTitle('ROLE_BINIOUFOUS');

            if (\in_array('ROLE_BINIOUFOUS', $user->getRoles(), true)) {
                $user->removeRole($binioufousRole);
                $this->addFlash('success', $user->getFullName().' n\'est plus "Membre".');
                $logger->info('Rôle ROLE_BINIOUFOUS retiré', ['target' => $user->getEmail(), 'by' => $this->getUser()?->getUserIdentifier()]);
            } else {
                $user->addRole($binioufousRole);
                $this->addFlash('success', $user->getFullName().' est maintenant "Membre".');
                $logger->info('Rôle ROLE_BINIOUFOUS ajouté', ['target' => $user->getEmail(), 'by' => $this->getUser()?->getUserIdentifier()]);
            }

            $manager->persist($user);
            $manager->flush();
        }

        return $this->redirectToRoute('desk');
    }

    // Toggles the "image rights consent given" flag from the member's detail page. Consent itself is collected offline; this is just the recorded acknowledgement by an admin.
    #[Route('/admin/user/{slug}/toggle-image-rights', name: 'user_toggle_image_rights', methods: ['POST'])]
    public function toggleImageRightsConsent(#[MapEntity(mapping: ['slug' => 'slug'])] User $user, EntityManagerInterface $manager, Request $request, LoggerInterface $logger): Response
    {
        if ($this->isCsrfTokenValid('toggle_image_rights'.$user->getId(), $request->request->get('_token'))) {
            $user->setImageRightsConsent(!$user->getImageRightsConsent());
            $manager->persist($user);
            $manager->flush();

            $logger->info('Accord droit à l\'image '.($user->getImageRightsConsent() ? 'confirmé' : 'retiré'), ['target' => $user->getEmail(), 'by' => $this->getUser()?->getUserIdentifier()]);

            $this->addFlash('success', $user->getImageRightsConsent()
                ? $user->getFullName().' a donné son accord pour le droit à l\'image.'
                : 'Accord droit à l\'image retiré pour '.$user->getFullName().'.');
        }

        return $this->redirectToRoute('user_show', ['slug' => $user->getSlug()]);
    }

    // Overview of every validated account split by image rights consent, so the team can check who may appear in photos without opening each profile.
    #[Route('/admin/image-rights', name: 'image_rights')]
    public function imageRights(UserRepository $repo): Response
    {
        $users = $repo->findValidatedSortedByName();

        return $this->render('admin/image_rights.html.twig', [
            'granted' => array_filter($users, fn (User $user) => $user->getImageRightsConsent()),
            'unset' => array_filter($users, fn (User $user) => !$user->getImageRightsConsent()),
        ]);
    }

    // Same list as imageRights() as a CSV file, semicolon-separated with a UTF-8 BOM so it opens correctly in a French Excel/LibreOffice.
    #[Route('/admin/image-rights/export.csv', name: 'image_rights_export')]
    public function exportImageRights(UserRepository $repo): Response
    {
        $handle = fopen('php://temp', 'r+');
        if (false === $handle) {
            throw new \RuntimeException('Impossible de créer le fichier temporaire pour l\'export CSV du droit à l\'image.');
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Nom', 'Pseudo', 'Email', 'Instrument', 'Droit à l\'image'], ';');

        foreach ($repo->findValidatedSortedByName() as $user) {
            fputcsv($handle, array_map([self::class, 'escapeCsvFormula'], [
                $user->getFullName(),
                $user->getNickname(),
                $user->getEmail(),
                $user->getInstrument()?->getTitle() ?? '',
                $user->getImageRightsConsent() ? 'Oui' : 'Non',
            ]), ';');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="droit-a-l-image-'.date('Y-m-d').'.csv"');

        return $response;
    }

    // Names/nicknames are user input: a value starting with = + - @ would run as a formula once opened in a spreadsheet (CSV injection).
    private static function escapeCsvFormula(?string $value): string
    {
        $value = (string) $value;

        return '' !== $value && \in_array($value[0], ['=', '+', '-', '@'], true) ? "'".$value : $value;
    }

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

    // Validates a pending registration and emails the user. Grants no role: membership is decided separately via toggleMembership() above.
    #[Route('/admin/{slug}/valid', name: 'user_valid', methods: ['POST'])]
    public function validUser(EntityManagerInterface $manager, #[MapEntity(mapping: ['slug' => 'slug'])] User $user, Request $request, RegistrationMailer $registrationMailer, LoggerInterface $logger): Response
    {
        if ($this->isCsrfTokenValid('valid'.$user->getId(), $request->request->get('_token'))) {
            $user->setValidation(true);

            $manager->persist($user);
            $manager->flush();

            $registrationMailer->sendValidated($user);

            $logger->info('Inscription validée', ['target' => $user->getEmail(), 'by' => $this->getUser()?->getUserIdentifier()]);

            $this->addFlash(
                'success',
                'Utilisateur accepté'
            );
        }

        return $this->redirectToRoute('valid');
    }

    // Rejects a pending registration by deleting the account, since it was never validated.
    #[Route('/admin/user/{slug}/refuse', name: 'user_refuse', methods: ['DELETE'])]
    public function refuseUser(#[MapEntity(mapping: ['slug' => 'slug'])] User $user, EntityManagerInterface $manager, Request $request, LoggerInterface $logger): Response
    {
        if ($this->isCsrfTokenValid('refuse'.$user->getId(), $request->request->get('_token'))) {
            $logger->info('Inscription refusée', ['target' => $user->getEmail(), 'by' => $this->getUser()?->getUserIdentifier()]);

            $manager->remove($user);
            $manager->flush();

            $this->addFlash(
                'success',
                'Inscription refusée'
            );
        }

        return $this->redirectToRoute('valid');
    }

    // Validates or refuses every checked pending registration at once, same effect as the single-row buttons applied one by one.
    // Only still-unvalidated accounts are touched, so a forged request can't delete an already validated member.
    #[Route('/admin/valid/bulk', name: 'user_bulk_valid', methods: ['POST'])]
    public function bulkValidUsers(Request $request, UserRepository $repo, EntityManagerInterface $manager, RegistrationMailer $registrationMailer, LoggerInterface $logger): Response
    {
        if (!$this->isCsrfTokenValid('bulk_valid', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide, recharge la page et réessaie.');

            return $this->redirectToRoute('valid');
        }

        $action = $request->request->get('bulk_action');
        if (!\in_array($action, ['valid', 'refuse'], true)) {
            $this->addFlash('danger', 'Action groupée inconnue.');

            return $this->redirectToRoute('valid');
        }

        $users = array_filter(
            $repo->findBy(['id' => $request->request->all('user_ids')]),
            fn (User $user) => !$user->getValidation()
        );

        if ([] === $users) {
            $this->addFlash('warning', 'Aucune inscription sélectionnée.');

            return $this->redirectToRoute('valid');
        }

        foreach ($users as $user) {
            if ('valid' === $action) {
                $user->setValidation(true);
                $logger->info('Inscription validée', ['target' => $user->getEmail(), 'by' => $this->getUser()?->getUserIdentifier()]);
            } else {
                $manager->remove($user);
                $logger->info('Inscription refusée', ['target' => $user->getEmail(), 'by' => $this->getUser()?->getUserIdentifier()]);
            }
        }

        $manager->flush();

        // Mails go out only once the database write succeeded, so nobody is told they're accepted if the flush fails.
        if ('valid' === $action) {
            foreach ($users as $user) {
                $registrationMailer->sendValidated($user);
            }
        }

        $this->addFlash('success', \count($users).('valid' === $action ? ' inscription(s) validée(s)' : ' inscription(s) refusée(s)'));

        return $this->redirectToRoute('valid');
    }

    // Removes a single role from a user (the trash button on each role badge).
    #[Route('/admin/user/{slug}/role/{roleId}', name: 'user_remove_role', methods: ['DELETE'])]
    public function removeUserRole(#[MapEntity(mapping: ['slug' => 'slug'])] User $user, #[MapEntity(mapping: ['roleId' => 'id'])] Role $role, EntityManagerInterface $manager, Request $request, LoggerInterface $logger): Response
    {
        if ($this->isCsrfTokenValid('remove_role'.$user->getId().$role->getId(), $request->request->get('_token'))) {
            $user->removeRole($role);
            $manager->persist($user);
            $manager->flush();

            $logger->info('Rôle '.$role->getTitle().' retiré', ['target' => $user->getEmail(), 'by' => $this->getUser()?->getUserIdentifier()]);

            $this->addFlash(
                'success',
                'Rôle retiré'
            );
        }

        return $this->redirectToRoute('user_show', ['slug' => $user->getSlug()]);
    }
}
