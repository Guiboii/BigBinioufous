<?php

namespace App\Security;

use App\Entity\Folder;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

// Write access (create/move/delete/upload) to a /desk/files/{space}, separate from read access, which is handled by access_control in security.yaml. Subject is the space name (string).
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
