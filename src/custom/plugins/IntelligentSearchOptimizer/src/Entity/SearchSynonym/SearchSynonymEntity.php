<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Entity\SearchSynonym;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\Language\LanguageEntity;

class SearchSynonymEntity extends Entity
{
    use EntityIdTrait;

    protected string $keyword;
    protected string $synonym;
    protected ?string $languageId = null;
    protected ?string $salesChannelId = null;
    protected bool $active = true;
    protected ?LanguageEntity $language = null;
    protected ?SalesChannelEntity $salesChannel = null;

    public function getKeyword(): string
    {
        return $this->keyword;
    }

    public function setKeyword(string $keyword): void
    {
        $this->keyword = $keyword;
    }

    public function getSynonym(): string
    {
        return $this->synonym;
    }

    public function setSynonym(string $synonym): void
    {
        $this->synonym = $synonym;
    }

    public function getLanguageId(): ?string
    {
        return $this->languageId;
    }

    public function setLanguageId(?string $languageId): void
    {
        $this->languageId = $languageId;
    }

    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(?string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getLanguage(): ?LanguageEntity
    {
        return $this->language;
    }

    public function setLanguage(?LanguageEntity $language): void
    {
        $this->language = $language;
    }

    public function getSalesChannel(): ?SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function setSalesChannel(?SalesChannelEntity $salesChannel): void
    {
        $this->salesChannel = $salesChannel;
    }
}