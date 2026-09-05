<?php

namespace App\Tests\Entity;

use App\Entity\Role;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testGetRolesReflectsAssignedRoles(): void
    {
        $user = new User();
        $user->addRole((new Role())->setTitle('ROLE_ADMIN')->setDescription('Administrator'));
        $user->addRole((new Role())->setTitle('ROLE_BINIOUFOUS')->setDescription('Binioufous'));

        $this->assertSame(['ROLE_ADMIN', 'ROLE_BINIOUFOUS'], $user->getRoles());
    }

    public function testGetFullNameFallsBackToNicknameWhenIdentityIsEmpty(): void
    {
        $user = (new User())->setNickname('Biniou42');

        $this->assertSame('Biniou42', $user->getFullName());
    }

    public function testGetFullNameCapitalisesGivenNameAndUppercasesFamilyName(): void
    {
        $user = (new User())->setNickname('Biniou42')->setFirstName('Marine')->setLastName('Gonnord');

        $this->assertSame('Marine GONNORD', $user->getFullName());
    }

    public function testGetFullNameNormalisesStoredCasingAndCompoundNames(): void
    {
        $user = (new User())->setFirstName('jEAN-luc')->setLastName('de la tour');

        $this->assertSame('Jean-Luc DE LA TOUR', $user->getFullName());
    }

    public function testUserIdentifierIsTheEmail(): void
    {
        $user = (new User())->setEmail('marine@example.test');

        $this->assertSame('marine@example.test', $user->getUserIdentifier());
    }
}
