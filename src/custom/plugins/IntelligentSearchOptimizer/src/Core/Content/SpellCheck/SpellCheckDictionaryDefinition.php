<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Core\Content\SpellCheck;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class SpellCheckDictionaryDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'search_optimizer_dictionary';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return SpellCheckDictionaryEntity::class;
    }

    public function getCollectionClass(): string
    {
        return SpellCheckDictionaryCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('word', 'word'))->addFlags(new Required()),
            (new IntField('frequency', 'frequency'))->addFlags(new Required()),
            (new StringField('language', 'language'))->addFlags(new Required()),
            new StringField('type', 'type'),
        ]);
    }
}