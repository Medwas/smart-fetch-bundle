<?php

namespace Verclam\SmartFetchBundle\Fetcher\Hydrator;

use Verclam\SmartFetchBundle\Fetcher\Configuration\Configuration;
use Verclam\SmartFetchBundle\Fetcher\TreeBuilder\Component\Component;

interface SmartFetchHydratorInterface
{
    public function hydrate(Component $component): void;

    public function support(Component $component, Configuration $configuration): bool;

}