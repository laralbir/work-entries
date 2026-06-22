<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use Symfony\Component\Uid\Uuid;

final readonly class GetUserQuery
{
    public function __construct(public Uuid $userId) {}
}
