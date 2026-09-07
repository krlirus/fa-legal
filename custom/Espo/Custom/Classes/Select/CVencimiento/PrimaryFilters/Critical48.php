<?php
declare(strict_types=1);

namespace Espo\Custom\Classes\Select\CVencimiento\PrimaryFilters;

use DateTimeImmutable;
use DateTimeZone;
use Espo\Core\Select\Primary\Filter;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\SelectBuilder;
class Critical48 implements Filter
{
    public function apply(SelectBuilder $queryBuilder): void
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $limit = $now->modify('+48 hours');

        $queryBuilder->where(
            Cond::and(
                Cond::equal(Cond::column('status'), 'Planned'),
                Cond::greaterOrEqual(
                    Cond::column('dateStart'),
                    $now->format('Y-m-d H:i:s')
                ),
                Cond::lessOrEqual(
                    Cond::column('dateStart'),
                    $limit->format('Y-m-d H:i:s')
                )
            )
        );
    }
}