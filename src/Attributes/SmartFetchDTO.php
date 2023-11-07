<?php

namespace Verclam\SmartFetchBundle\Attributes;


#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class SmartFetchDTO extends SmartFetch
{
    public function __construct(
        string $queryName,
        string $class = null,
        string $argumentName = null,
        bool   $isCollection = false,
        string $entityManager = null
    )
    {
        parent::__construct($queryName, $class, $argumentName, $isCollection, $entityManager);
    }
}