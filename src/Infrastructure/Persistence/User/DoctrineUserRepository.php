<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\User;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
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

    public function findAll(
        ?string $name = null,
        ?string $email = null,
        int $offset = 0,
        int $limit = 20,
    ): array {
        return $this->buildFilterQuery($name, $email)
            ->select('u')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countAll(?string $name = null, ?string $email = null): int
    {
        return (int) $this->buildFilterQuery($name, $email)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function buildFilterQuery(?string $name, ?string $email): QueryBuilder
    {
        $qb = $this->em->createQueryBuilder()
            ->from(User::class, 'u')
            ->where('u.deletedAt IS NULL')
            ->orderBy('u.name', 'ASC');

        if ($name !== null) {
            $qb->andWhere('LOWER(u.name) LIKE :name')
               ->setParameter('name', '%' . strtolower($name) . '%');
        }

        if ($email !== null) {
            $qb->andWhere('LOWER(u.email) LIKE :email')
               ->setParameter('email', '%' . strtolower($email) . '%');
        }

        return $qb;
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
