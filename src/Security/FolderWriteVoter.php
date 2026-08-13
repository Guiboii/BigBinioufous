<?php

namespace App\Security;

use App\Entity\Folder;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Écriture (créer/déplacer/supprimer/uploader) dans un espace de
 * /desk/files/{space}, distincte de la lecture (gérée par access_control
 * dans security.yaml). Sujet = le space (string), cf. Folder::WRITE_ROLES.
 */
class FolderWriteVoter extends Voter
{
    public const WRITE = 'FOLDER_WRITE';

    protected function supports(string $attribute, $subject): bool
    {
        return self::WRITE === $attribute && \is_string($subject);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $allowedRoles = Folder::WRITE_ROLES[$subject] ?? [];

        return [] !== array_intersect($allowedRoles, $user->getRoles());
    }
}
