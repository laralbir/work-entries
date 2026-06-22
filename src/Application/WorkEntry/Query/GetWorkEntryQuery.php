<?php

declare(strict_types=1);

namespace App\Application\WorkEntry\Query;

use Symfony\Component\Uid\Uuid;

final readonly class GetWorkEntryQuery
{
    public function __construct(public Uuid $workEntryId) {}
}
