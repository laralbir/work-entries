<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\WorkEntry;

use App\Domain\WorkEntry\Repository\WorkEntryRepositoryInterface;
use App\Entity\User;
use App\Entity\WorkEntry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

final class DoctrineWorkEntryRepository implements WorkEntryRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findById(Uuid $id): ?WorkEntry
    {
        return $this->em->getRepository(WorkEntry::class)
            ->findOneBy(['id' => $id, 'deletedAt' => null]);
    }

    public function findByUser(
        User $user,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null,
        int $offset = 0,
        int $limit = 20,
    ): array {
        return $this->buildUserQuery($user, $from, $to)
            ->select('w')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByUser(
        User $user,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null,
    ): int {
        return (int) $this->buildUserQuery($user, $from, $to)
            ->select('COUNT(w.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function buildUserQuery(
        User $user,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
    ): QueryBuilder {
        $qb = $this->em->createQueryBuilder()
            ->from(WorkEntry::class, 'w')
            ->join('w.user', 'u')
            ->where('u.id = :userId')
            ->andWhere('w.deletedAt IS NULL')
            ->orderBy('w.startDate', 'DESC')
            ->setParameter('userId', $user->getId(), UuidType::NAME);

        if ($from !== null) {
            $qb->andWhere('w.startDate >= :from')->setParameter('from', $from);
        }

        if ($to !== null) {
            $qb->andWhere('w.startDate <= :to')->setParameter('to', $to);
        }

        return $qb;
    }

    public function findOverlapping(
        User $user,
        \DateTimeImmutable $startDate,
        ?\DateTimeImmutable $endDate,
        ?Uuid $excludeId = null,
    ): array {
        $qb = $this->em->createQueryBuilder()
            ->select('w')
            ->from(WorkEntry::class, 'w')
            ->join('w.user', 'u')
            ->where('u.id = :userId')
            ->andWhere('w.deletedAt IS NULL')
            ->andWhere('(w.endDate IS NULL OR w.endDate > :startDate)')
            ->setParameter('userId', $user->getId(), UuidType::NAME)
            ->setParameter('startDate', $startDate);

        if ($endDate !== null) {
            $qb->andWhere('w.startDate < :endDate')
               ->setParameter('endDate', $endDate);
        }

        if ($excludeId !== null) {
            $qb->andWhere('w.id != :excludeId')
               ->setParameter('excludeId', $excludeId, UuidType::NAME);
        }

        return $qb->getQuery()->getResult();
    }

    public function save(WorkEntry $workEntry): void
    {
        $this->em->persist($workEntry);
    }

    public function flush(): void
    {
        $this->em->flush();
    }
}
