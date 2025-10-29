<?php declare(strict_types=1);

namespace Swag\IntelligentSearchOptimizer\Subscriber;

use Shopware\Core\Framework\Struct\Struct;

class SearchSuggestionsStruct extends Struct
{
    protected array $suggestions;

    public function __construct(array $suggestions)
    {
        $this->suggestions = $suggestions;
    }

    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    public function hasSuggestions(): bool
    {
        return !empty($this->suggestions);
    }

    public function getApiAlias(): string
    {
        return 'search_suggestions';
    }
}