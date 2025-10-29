<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Core\Content\SpellCheck;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(SpellCheckDictionaryEntity $entity)
 * @method void set(string $key, SpellCheckDictionaryEntity $entity)
 * @method SpellCheckDictionaryEntity[] getIterator()
 * @method SpellCheckDictionaryEntity[] getElements()
 * @method SpellCheckDictionaryEntity|null get(string $key)
 * @method SpellCheckDictionaryEntity|null first()
 * @method SpellCheckDictionaryEntity|null last()
 */
class SpellCheckDictionaryCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SpellCheckDictionaryEntity::class;
    }
}