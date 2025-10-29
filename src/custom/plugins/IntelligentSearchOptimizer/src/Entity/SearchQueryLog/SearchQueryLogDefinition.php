<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Entity\SearchQueryLog;

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
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Product\ProductDefinition;

class SearchQueryLogDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'search_query_log';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return SearchQueryLogEntity::class;
    }

    public function getCollectionClass(): string
    {
        return SearchQueryLogCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('search_term', 'searchTerm'))->addFlags(new Required()),
            (new StringField('normalized_term', 'normalizedTerm'))->addFlags(new Required()),
            (new IntField('result_count', 'resultCount'))->addFlags(new Required()),
            new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class),
            new FkField('language_id', 'languageId', LanguageDefinition::class),
            new FkField('customer_id', 'customerId', CustomerDefinition::class),
            new StringField('session_id', 'sessionId'),
            new FkField('clicked_product_id', 'clickedProductId', ProductDefinition::class),
            new BoolField('converted', 'converted'),
            new StringField('search_source', 'searchSource'),
            (new DateTimeField('created_at', 'createdAt'))->addFlags(new Required()),
            new DateTimeField('updated_at', 'updatedAt'),
            
            new ManyToOneAssociationField('salesChannel', 'sales_channel_id', SalesChannelDefinition::class, 'id', false),
            new ManyToOneAssociationField('language', 'language_id', LanguageDefinition::class, 'id', false),
            new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id', false),
            new ManyToOneAssociationField('clickedProduct', 'clicked_product_id', ProductDefinition::class, 'id', false),
        ]);
    }
}