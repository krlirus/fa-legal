<?php
declare(strict_types=1);

namespace Espo\Custom\Classes\Select\CVencimiento\PrimaryFilters;

use DateTimeImmutable;
use DateTimeZone;
use Espo\Core\Select\Primary\Filter;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\SelectBuilder;
class OverdueLegal implements Filter
{
    public function apply(SelectBuilder $queryBuilder): void
    {
        $now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->format('Y-m-d H:i:s');

        $queryBuilder->where(
            Cond::and(
                Cond::equal(Cond::column('status'), 'Planned'),
                Cond::less(Cond::column('dateStart'), $now)
            )
        );
    }
}