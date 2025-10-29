<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Entity\SearchRedirect;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(SearchRedirectEntity $entity)
 * @method void set(string $key, SearchRedirectEntity $entity)
 * @method SearchRedirectEntity[] getIterator()
 * @method SearchRedirectEntity[] getElements()
 * @method SearchRedirectEntity|null get(string $key)
 * @method SearchRedirectEntity|null first()
 * @method SearchRedirectEntity|null last()
 */
class SearchRedirectCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SearchRedirectEntity::class;
    }
}