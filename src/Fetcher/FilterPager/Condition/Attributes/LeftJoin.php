<?php

namespace Verclam\SmartFetchBundle\Fetcher\FilterPager\Condition\Attributes;

use Attribute;
use Exception;

#[\Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class LeftJoin extends AbstractCondition implements SmartFetchConditionInterface
{
    public array $joins;

    /** @var array<string, string>  */
    public array $joinedAliases = [];

    /**
     * @throws Exception
     */
    public function __construct(
        string|array $join,
        public bool $withVersion = false,
        public ?string $className = null
    ) {
        $this->joins = is_array($join) ? $join : [$join];

        if ($this->withVersion) {
            $this->validateAttribute();
        }
    }

    /**
     * @throws Exception
     */
    private function validateAttribute(): void
    {
        if (!$this->className) {
            throw new Exception('className is required when withVersion is true');
        }
    }

    /**
     * @param string $fieldName
     * @param string $alias
     * @return void
     */
    public function addJoinedAliases(string $fieldName, string $alias): void
    {
        $this->joinedAliases[$fieldName] = $alias;
    }

    /**
     * @return array<string, string>
     */
    public function getJoinedAliases(): array
    {
        return $this->joinedAliases;
    }

    public function isAlreadyJoined(string $joinFieldAlias): bool
    {
        return in_array($joinFieldAlias, $this->joinedAliases);
    }

}