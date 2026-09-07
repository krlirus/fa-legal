<?php
declare(strict_types=1);

namespace Espo\Custom\Classes\Select\CVencimiento\PrimaryFilters;

use DateTimeImmutable;
use DateTimeZone;
use Espo\Core\Select\Primary\Filter;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\SelectBuilder;
class NormalLegal implements Filter
{
    public function apply(SelectBuilder $queryBuilder): void
    {
        $limit = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->modify('+7 days')
            ->format('Y-m-d H:i:s');

        $queryBuilder->where(
            Cond::and(
                Cond::equal(Cond::column('status'), 'Planned'),
                Cond::greater(Cond::column('dateStart'), $limit)
            )
        );
    }
}