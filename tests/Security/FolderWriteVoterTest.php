<?php

namespace App\Tests\Security;

use App\Entity\Folder;
use App\Entity\Role;
use App\Entity\User;
use App\Security\FolderWriteVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class FolderWriteVoterTest extends TestCase
{
    public function testBinioufousCanWriteToMusicSpace(): void
    {
        $user = $this->userWithRoles(['ROLE_BINIOUFOUS']);

        $vote = $this->vote($user, Folder::SPACE_MUSIC);

        $this->assertSame(Voter::ACCESS_GRANTED, $vote);
    }

    public function testSimpleValidatedAccountCannotWriteToMusicSpace(): void
    {
        $user = $this->userWithRoles([]);

        $vote = $this->vote($user, Folder::SPACE_MUSIC);

        $this->assertSame(Voter::ACCESS_DENIED, $vote);
    }

    public function testOnlyRoleAdminCanWriteToTheOtherSpace(): void
    {
        $accountant = $this->userWithRoles(['ROLE_COMPTA']);
        $admin = $this->userWithRoles(['ROLE_ADMIN']);

        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($accountant, Folder::SPACE_OTHER));
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($admin, Folder::SPACE_OTHER));
    }

    private function userWithRoles(array $roleTitles): User
    {
        $user = new User();
        foreach ($roleTitles as $title) {
            $user->addRole((new Role())->setTitle($title)->setDescription($title));
        }

        return $user;
    }

    private function vote(User $user, string $space): int
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return (new FolderWriteVoter())->vote($token, $space, [FolderWriteVoter::WRITE]);
    }
}
