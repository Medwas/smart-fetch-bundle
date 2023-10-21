<?php

    namespace Verclam\SmartFetchBundle\DependecyInjection;

    use Symfony\Component\Config\FileLocator;
    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\DependencyInjection\Extension\Extension;
    use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

    class SmartFetchExtension extends Extension
    {
        /**
         * @inheritDoc
         * @throws \Exception
         */
        public function load(array $configs , ContainerBuilder $container)
        {
            $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Ressources/config'));
            $loader->load('services.xml');

        }
    }