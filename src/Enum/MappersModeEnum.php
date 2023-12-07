<?php

namespace Verclam\SmartFetchBundle\Enum;

enum MappersModeEnum
{
    case SERIALIZATION_GROUPS;
    case ENTITY_ASSOCIATIONS;

    public function validateMappers(mixed $mappers): void
    {
        if ($this === MappersModeEnum::SERIALIZATION_GROUPS) {
            if (!is_string($mappers)) {
                throw new \Error('SERIALIZATION_GROUPS mode has to be an array');
            }
        }
    }
}