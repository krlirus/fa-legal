<?php
declare(strict_types=1);

namespace Espo\Custom\Classes\Select\CExpediente\PrimaryFilters;

use DateTimeImmutable;
use DateTimeZone;
use Espo\Core\Select\Primary\Filter;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\SelectBuilder;

class Stale30 implements Filter
{
    public function apply(SelectBuilder $queryBuilder): void
    {
        $cutoff = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->modify('-30 days')
            ->format('Y-m-d');

        $queryBuilder->where(
            Cond::or(
                Cond::less(Cond::column('lastDocketDate'), $cutoff),
                Cond::and(
                    Cond::equal(Cond::column('lastDocketDate'), null),
                    Cond::less(Cond::column('startDate'), $cutoff)
                )
            )
        );
    }
}