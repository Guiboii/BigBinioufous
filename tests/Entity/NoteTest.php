<?php

namespace App\Tests\Entity;

use App\Entity\Note;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class NoteTest extends TestCase
{
    public function testIsAuthoredByComparesUserIdNotObjectIdentity(): void
    {
        $author = $this->userWithId(1);
        $sameAuthorFromAnotherEntityManager = $this->userWithId(1);
        $otherUser = $this->userWithId(2);
        $note = (new Note())->setAuthor($author);

        $this->assertTrue($note->isAuthoredBy($author));
        $this->assertTrue($note->isAuthoredBy($sameAuthorFromAnotherEntityManager));
        $this->assertFalse($note->isAuthoredBy($otherUser));
    }

    // User's id is Doctrine-generated (no public setter), set via reflection to simulate two distinct
    // entity instances that represent the same database row, as happens across separate EntityManagers.
    private function userWithId(int $id): User
    {
        $user = new User();
        $property = new \ReflectionProperty(User::class, 'id');
        $property->setAccessible(true);
        $property->setValue($user, $id);

        return $user;
    }
}
