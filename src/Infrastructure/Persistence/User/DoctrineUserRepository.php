<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\User;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findById(Uuid $id): ?User
    {
        return $this->em->getRepository(User::class)
            ->findOneBy(['id' => $id, 'deletedAt' => null]);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->em->getRepository(User::class)
            ->findOneBy(['email' => $email, 'deletedAt' => null]);
    }

    public function findAll(): array
    {
        return $this->em->getRepository(User::class)
            ->findBy(['deletedAt' => null]);
    }

    public function save(User $user): void
    {
        $this->em->persist($user);
    }

    public function flush(): void
    {
        $this->em->flush();
    }
}
