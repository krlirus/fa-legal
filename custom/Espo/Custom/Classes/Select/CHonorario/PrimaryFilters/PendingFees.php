<?php

declare(strict_types=1);

namespace Espo\Custom\Classes\Select\CHonorario\PrimaryFilters;

use Espo\Core\Select\Primary\Filter;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Query\Part\Condition as Cond;

/**
 * F&A Legal.
 * Honorarios que aún no constan como pagados.
 */
class PendingFees implements Filter
{
    public function apply(SelectBuilder $queryBuilder): void
    {
        $queryBuilder->where(
            Cond::notIn(
                Cond::column('feeStatus'),
                ['Pagado']
            )
        );
    }
}
