<?php

declare(strict_types=1);

namespace App\Domain\WorkEntry\Event;

use App\Entity\WorkEntry;

final class WorkEntryDeletedEvent
{
    public function __construct(public readonly WorkEntry $workEntry) {}
}
