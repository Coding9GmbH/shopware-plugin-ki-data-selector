<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Entity\SearchRedirect;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\System\Language\LanguageDefinition;

class SearchRedirectDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'search_redirect';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return SearchRedirectEntity::class;
    }

    public function getCollectionClass(): string
    {
        return SearchRedirectCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('search_term', 'searchTerm'))->addFlags(new Required()),
            (new StringField('target_url', 'targetUrl'))->addFlags(new Required()),
            (new StringField('target_type', 'targetType'))->addFlags(new Required()),
            new IdField('target_entity_id', 'targetEntityId'),
            new FkField('language_id', 'languageId', LanguageDefinition::class),
            new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class),
            new BoolField('active', 'active'),
            new IntField('priority', 'priority'),
            (new DateTimeField('created_at', 'createdAt'))->addFlags(new Required()),
            new DateTimeField('updated_at', 'updatedAt'),
            
            new ManyToOneAssociationField('language', 'language_id', LanguageDefinition::class, 'id', false),
            new ManyToOneAssociationField('salesChannel', 'sales_channel_id', SalesChannelDefinition::class, 'id', false),
        ]);
    }
}