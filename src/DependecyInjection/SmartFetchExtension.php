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
            //TODO: organise the xml files by objectifs and load it sequentially
            $loader->load('services.xml');
            $loader->load('filter-pager-services.xml');

            if(interface_exists('Symfony\Component\HttpKernel\Controller\ValueResolverInterface')){
                $loader->load('after-symfony-6.2.xml');
            }else{
                $loader->load('prior-symfony-6.2.xml');
            }

        }
    }