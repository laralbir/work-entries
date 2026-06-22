<?php

declare(strict_types=1);

namespace App\State\WorkEntry;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiInput\WorkEntryInput;
use App\Application\WorkEntry\Command\CreateWorkEntryCommand;
use App\Application\WorkEntry\Command\CreateWorkEntryHandler;
use App\Entity\User;
use App\Entity\WorkEntry;
use Symfony\Bundle\SecurityBundle\Security;

final class WorkEntryCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CreateWorkEntryHandler $handler,
        private readonly Security $security,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): WorkEntry
    {
        /** @var User $user */
        $user = $this->security->getUser();

        /** @var WorkEntryInput $data */
        return ($this->handler)(new CreateWorkEntryCommand(
            user: $user,
            startDate: $data->startDate ?? new \DateTimeImmutable(),
            endDate: $data->endDate,
        ));
    }
}
