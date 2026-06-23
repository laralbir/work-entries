<?php

declare(strict_types=1);

namespace App\Application\WorkEntry\Query;

use App\Entity\WorkEntry;

final readonly class WorkEntriesPage
{
    /** @param WorkEntry[] $items */
    public function __construct(
        public array $items,
        public int $totalItems,
    ) {}
}
