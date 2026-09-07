<?php
declare(strict_types=1);

namespace Espo\Custom\Classes\Select\CVencimiento\PrimaryFilters;

use DateTimeImmutable;
use DateTimeZone;
use Espo\Core\Select\Primary\Filter;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\SelectBuilder;
class Upcoming7 implements Filter
{
    public function apply(SelectBuilder $queryBuilder): void
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $from = $now->modify('+48 hours');
        $to = $now->modify('+7 days');

        $queryBuilder->where(
            Cond::and(
                Cond::equal(Cond::column('status'), 'Planned'),
                Cond::greater(
                    Cond::column('dateStart'),
                    $from->format('Y-m-d H:i:s')
                ),
                Cond::lessOrEqual(
                    Cond::column('dateStart'),
                    $to->format('Y-m-d H:i:s')
                )
            )
        );
    }
}