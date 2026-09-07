<?php

declare(strict_types=1);

namespace Espo\Custom\Classes\Select\CAsunto\PrimaryFilters;

use Espo\Core\Select\Primary\Filter;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Query\Part\Condition as Cond;

/**
 * F&A Legal.
 * Asuntos que todavía requieren gestión.
 */
class ActiveLegal implements Filter
{
    public function apply(SelectBuilder $queryBuilder): void
    {
        $queryBuilder->where(
            Cond::notIn(
                Cond::column('matterStatus'),
                ['Finalizado', 'Archivado', 'Suspendido']
            )
        );
    }
}
