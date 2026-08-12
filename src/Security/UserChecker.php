<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Bloque réellement la connexion tant qu'un compte n'a pas été accepté par
 * un·e admin (User::$validation), demande explicite de l'utilisatrice le
 * 2026-08-12 : jusqu'ici $validation ne servait qu'à afficher un bandeau
 * "en attente" une fois déjà connecté (templates/desk/index.html.twig,
 * retiré au passage puisque désormais inatteignable), rien n'empêchait
 * vraiment de se connecter et d'utiliser /desk avant validation.
 */
class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->getValidation()) {
            throw new CustomUserMessageAccountStatusException('register.pending_login_error');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
