<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase as BaseApiTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

abstract class ApiTestCase extends BaseApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;
    protected function getEntityManager(): EntityManagerInterface
    {
        if (!static::$booted) {
            static::bootKernel();
        }

        return static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function truncateTables(): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $connection->executeStatement('TRUNCATE TABLE work_entries');
        $connection->executeStatement('TRUNCATE TABLE users');
        $connection->executeStatement('TRUNCATE TABLE doctrine_migration_versions');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }

    protected function createUser(string $email, string $plainPassword, string $name = 'Test User'): User
    {
        $em = $this->getEntityManager();
        $hasher = static::getContainer()->get('security.user_password_hasher');

        $user = new User($name, $email);
        $user->setPassword($hasher->hashPassword($user, $plainPassword));

        $em->persist($user);
        $em->flush();

        return $user;
    }

    protected function getToken(string $email, string $password): string
    {
        $response = static::createClient()->request('POST', '/api/login_check', [
            'json' => ['email' => $email, 'password' => $password],
        ]);

        return $response->toArray()['token'];
    }

    protected function authHeaders(string $token): array
    {
        return ['headers' => ['Authorization' => 'Bearer ' . $token]];
    }
}
