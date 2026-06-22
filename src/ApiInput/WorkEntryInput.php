<?php

declare(strict_types=1);

namespace App\ApiInput;

use Symfony\Component\Serializer\Attribute\Groups;

final class WorkEntryInput
{
    #[Groups(['work_entry:write'])]
    public ?\DateTimeImmutable $startDate = null;

    #[Groups(['work_entry:write'])]
    public ?\DateTimeImmutable $endDate = null;
}
