<?php

declare(strict_types=1);

namespace App\State\WorkEntry;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\WorkEntry\Command\ClockOutCommand;
use App\Application\WorkEntry\Command\ClockOutHandler;
use App\Entity\User;
use App\Entity\WorkEntry;
use Symfony\Bundle\SecurityBundle\Security;

final class ClockOutProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ClockOutHandler $handler,
        private readonly Security $security,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): WorkEntry
    {
        /** @var User $user */
        $user = $this->security->getUser();

        return ($this->handler)(new ClockOutCommand(
            workEntryId: $uriVariables['id'],
            currentUser: $user,
        ));
    }
}
