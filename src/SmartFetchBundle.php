<?php

    namespace Verclam\SmartFetchBundle;

    use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
    use Symfony\Component\HttpKernel\Bundle\Bundle;
    use Verclam\SmartFetchBundle\DependecyInjection\SmartFetchExtension;

    class SmartFetchBundle extends Bundle
    {
        public function getContainerExtension(): ?ExtensionInterface
        {
            return new SmartFetchExtension();
        }

    }