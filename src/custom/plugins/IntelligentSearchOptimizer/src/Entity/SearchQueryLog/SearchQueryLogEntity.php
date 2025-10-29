<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Entity\SearchQueryLog;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Product\ProductEntity;

class SearchQueryLogEntity extends Entity
{
    use EntityIdTrait;

    protected string $searchTerm;
    protected string $normalizedTerm;
    protected int $resultCount;
    protected ?string $salesChannelId = null;
    protected ?string $languageId = null;
    protected ?string $customerId = null;
    protected ?string $sessionId = null;
    protected ?string $clickedProductId = null;
    protected bool $converted = false;
    protected ?string $searchSource = null;
    protected ?SalesChannelEntity $salesChannel = null;
    protected ?LanguageEntity $language = null;
    protected ?CustomerEntity $customer = null;
    protected ?ProductEntity $clickedProduct = null;

    public function getSearchTerm(): string
    {
        return $this->searchTerm;
    }

    public function setSearchTerm(string $searchTerm): void
    {
        $this->searchTerm = $searchTerm;
    }

    public function getNormalizedTerm(): string
    {
        return $this->normalizedTerm;
    }

    public function setNormalizedTerm(string $normalizedTerm): void
    {
        $this->normalizedTerm = $normalizedTerm;
    }

    public function getResultCount(): int
    {
        return $this->resultCount;
    }

    public function setResultCount(int $resultCount): void
    {
        $this->resultCount = $resultCount;
    }

    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(?string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function getLanguageId(): ?string
    {
        return $this->languageId;
    }

    public function setLanguageId(?string $languageId): void
    {
        $this->languageId = $languageId;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function setCustomerId(?string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(?string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function getClickedProductId(): ?string
    {
        return $this->clickedProductId;
    }

    public function setClickedProductId(?string $clickedProductId): void
    {
        $this->clickedProductId = $clickedProductId;
    }

    public function isConverted(): bool
    {
        return $this->converted;
    }

    public function setConverted(bool $converted): void
    {
        $this->converted = $converted;
    }

    public function getSearchSource(): ?string
    {
        return $this->searchSource;
    }

    public function setSearchSource(?string $searchSource): void
    {
        $this->searchSource = $searchSource;
    }

    public function getSalesChannel(): ?SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function setSalesChannel(?SalesChannelEntity $salesChannel): void
    {
        $this->salesChannel = $salesChannel;
    }

    public function getLanguage(): ?LanguageEntity
    {
        return $this->language;
    }

    public function setLanguage(?LanguageEntity $language): void
    {
        $this->language = $language;
    }

    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    public function setCustomer(?CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }

    public function getClickedProduct(): ?ProductEntity
    {
        return $this->clickedProduct;
    }

    public function setClickedProduct(?ProductEntity $clickedProduct): void
    {
        $this->clickedProduct = $clickedProduct;
    }
}