<?php

namespace App\Tests\Support;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

// Base class for functional tests: fresh SQLite schema per test (works around doctrine:migrations being broken on this project, cf. CLAUDE.md) plus small helpers for auth/CSRF.
abstract class AppWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;
    private int $userCounter = 0;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    // Persists a User with a known password ('password123') and the given role titles, creating missing Role rows on the fly.
    protected function createUser(array $roleTitles = [], bool $validated = true): User
    {
        ++$this->userCounter;

        $user = new User();
        $user->setNickname('user'.$this->userCounter)
            ->setEmail(sprintf('user%d@example.test', $this->userCounter))
            ->setValidation($validated);

        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setHash($hasher->hashPassword($user, 'password123'));

        foreach ($roleTitles as $title) {
            $user->addRole($this->findOrCreateRole($title));
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    // Seeds a Role row without attaching it to any user: needed when a test exercises a role that must exist in the
    // database (e.g. looked up via RoleRepository::findOneByTitle() in a controller) but isn't held by any test user.
    protected function ensureRole(string $title): Role
    {
        $role = $this->findOrCreateRole($title);
        $this->entityManager->flush();

        return $role;
    }

    private function findOrCreateRole(string $title): Role
    {
        $role = $this->entityManager->getRepository(Role::class)->findOneBy(['title' => $title]);
        if (!$role) {
            $role = (new Role())->setTitle($title)->setDescription($title);
            $this->entityManager->persist($role);
        }

        return $role;
    }

    // KernelBrowser reboots the kernel (and clears the entity manager's identity map) on every request after the first,
    // so an entity fetched/persisted before a request can't be refresh()-ed afterwards ("entity is not managed"). Re-fetching
    // by id through a freshly obtained EntityManager works instead, since the underlying SQLite file already has the row.
    protected function reload(object $entity): object
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        return $entityManager->find($entity::class, $entity->getId());
    }

    // Matches the token id passed to isCsrfTokenValid() in controllers that check CSRF by hand (not through a Symfony Form).
    // Writes the token directly into the browser's session (same storage/key SessionTokenStorage reads from) rather than
    // going through security.csrf.token_manager, which requires a request currently pushed on the request stack: there
    // isn't one between two $this->client->request() calls in a test. $this->client->getSession() reuses the existing
    // session cookie (e.g. the one loginUser() set) instead of starting an unrelated one, so login state survives.
    protected function csrfToken(string $id): string
    {
        $session = $this->client->getSession();
        $token = bin2hex(random_bytes(20));
        $session->set('_csrf/'.$id, $token);
        $session->save();

        return $token;
    }
}
