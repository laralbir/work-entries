<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\ApiInput\WorkEntryInput;
use App\State\WorkEntry\ClockInProcessor;
use App\State\WorkEntry\ClockOutProcessor;
use App\State\WorkEntry\WorkEntryCollectionProvider;
use App\State\WorkEntry\WorkEntryCreateProcessor;
use App\State\WorkEntry\WorkEntryDeleteProcessor;
use App\State\WorkEntry\WorkEntryItemProvider;
use App\State\WorkEntry\WorkEntryUpdateProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            provider: WorkEntryCollectionProvider::class,
        ),
        new Get(
            security: 'object.getUser() === user',
            provider: WorkEntryItemProvider::class,
        ),
        new Post(
            input: WorkEntryInput::class,
            processor: WorkEntryCreateProcessor::class,
        ),
        new Put(
            security: 'object.getUser() === user',
            provider: WorkEntryItemProvider::class,
            processor: WorkEntryUpdateProcessor::class,
        ),
        new Patch(
            security: 'object.getUser() === user',
            provider: WorkEntryItemProvider::class,
            processor: WorkEntryUpdateProcessor::class,
        ),
        new Delete(
            security: 'object.getUser() === user',
            provider: WorkEntryItemProvider::class,
            processor: WorkEntryDeleteProcessor::class,
        ),
        new Post(
            uriTemplate: '/work_entries/clock-in',
            name: 'work_entry_clock_in',
            input: false,
            processor: ClockInProcessor::class,
        ),
        new Post(
            uriTemplate: '/work_entries/{id}/clock-out',
            name: 'work_entry_clock_out',
            input: false,
            processor: ClockOutProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['work_entry:read']],
    denormalizationContext: ['groups' => ['work_entry:write']],
)]
#[ORM\Entity]
#[ORM\Table(name: 'work_entries')]
#[ORM\Index(columns: ['user_id', 'start_date'], name: 'IDX_WE_USER_START_DATE')]
#[ORM\HasLifecycleCallbacks]
class WorkEntry
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'workEntries')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['work_entry:read', 'work_entry:write'])]
    #[Assert\NotBlank]
    private \DateTimeImmutable $startDate;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['work_entry:read', 'work_entry:write'])]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['work_entry:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['work_entry:read'])]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['work_entry:read'])]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct(User $user, \DateTimeImmutable $startDate, ?\DateTimeImmutable $endDate = null)
    {
        $this->user      = $user;
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // -------------------------------------------------------------------------
    // Lifecycle callbacks
    // -------------------------------------------------------------------------

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // -------------------------------------------------------------------------
    // Getters & Setters
    // -------------------------------------------------------------------------

    #[Groups(['work_entry:read'])]
    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    #[Groups(['work_entry:read'])]
    #[SerializedName('userId')]
    public function getUserId(): ?string
    {
        return $this->user->getId()?->toRfc4122();
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function softDelete(): static
    {
        $this->deletedAt = new \DateTimeImmutable();

        return $this;
    }

    public function restore(): static
    {
        $this->deletedAt = null;

        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function isActive(): bool
    {
        return $this->deletedAt === null && $this->endDate === null;
    }
}
