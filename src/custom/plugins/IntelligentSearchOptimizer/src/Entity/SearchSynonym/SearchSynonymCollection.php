<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Entity\SearchSynonym;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(SearchSynonymEntity $entity)
 * @method void set(string $key, SearchSynonymEntity $entity)
 * @method SearchSynonymEntity[] getIterator()
 * @method SearchSynonymEntity[] getElements()
 * @method SearchSynonymEntity|null get(string $key)
 * @method SearchSynonymEntity|null first()
 * @method SearchSynonymEntity|null last()
 */
class SearchSynonymCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SearchSynonymEntity::class;
    }
}