<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\WorkEntry;

use App\Domain\WorkEntry\Repository\WorkEntryRepositoryInterface;
use App\Entity\User;
use App\Entity\WorkEntry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class DoctrineWorkEntryRepository implements WorkEntryRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findById(Uuid $id): ?WorkEntry
    {
        return $this->em->getRepository(WorkEntry::class)
            ->findOneBy(['id' => $id, 'deletedAt' => null]);
    }

    public function findByUser(User $user): array
    {
        return $this->em->getRepository(WorkEntry::class)
            ->findBy(
                ['user' => $user, 'deletedAt' => null],
                ['startDate' => 'DESC'],
            );
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
