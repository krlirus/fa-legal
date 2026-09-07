<?php
declare(strict_types=1);

namespace Espo\Custom\Classes\Select\CVencimiento\PrimaryFilters;

use DateTimeImmutable;
use DateTimeZone;
use Espo\Core\Select\Primary\Filter;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\SelectBuilder;
class CompletedLegal implements Filter
{
    public function apply(SelectBuilder $queryBuilder): void
    {
        $queryBuilder->where(
            Cond::equal(Cond::column('status'), 'Held')
        );
    }
}