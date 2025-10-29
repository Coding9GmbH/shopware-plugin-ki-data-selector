<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Entity\SearchQueryLog;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(SearchQueryLogEntity $entity)
 * @method void set(string $key, SearchQueryLogEntity $entity)
 * @method SearchQueryLogEntity[] getIterator()
 * @method SearchQueryLogEntity[] getElements()
 * @method SearchQueryLogEntity|null get(string $key)
 * @method SearchQueryLogEntity|null first()
 * @method SearchQueryLogEntity|null last()
 */
class SearchQueryLogCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SearchQueryLogEntity::class;
    }
}