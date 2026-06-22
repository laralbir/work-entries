<?php

declare(strict_types=1);

namespace App\Application\WorkEntry\Query;

use App\Entity\User;

final readonly class ListWorkEntriesQuery
{
    public function __construct(public User $user) {}
}
