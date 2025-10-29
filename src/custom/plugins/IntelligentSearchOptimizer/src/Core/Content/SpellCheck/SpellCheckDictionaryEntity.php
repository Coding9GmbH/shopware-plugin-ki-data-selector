<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Core\Content\SpellCheck;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class SpellCheckDictionaryEntity extends Entity
{
    use EntityIdTrait;

    protected string $word;
    protected int $frequency;
    protected string $language;
    protected ?string $type;

    public function getWord(): string
    {
        return $this->word;
    }

    public function setWord(string $word): void
    {
        $this->word = $word;
    }

    public function getFrequency(): int
    {
        return $this->frequency;
    }

    public function setFrequency(int $frequency): void
    {
        $this->frequency = $frequency;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }
}