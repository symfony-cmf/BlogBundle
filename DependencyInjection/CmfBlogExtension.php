<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Symfony\Cmf\Bundle\BlogBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class CmfBlogExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        if (isset($config['persistence']['phpcr'])) {
            $this->loadPhpcrPersistence($config, $loader, $container);
        }

        if (isset($config['sonata_admin']) && $config['sonata_admin']['enabled']) {
            $this->loadSonataAdmin($config, $loader, $container);
        }

        if (isset($config['integrate_menu']) && $config['integrate_menu']['enabled']) {
            $this->loadMenuIntegration($config, $loader, $container);
        }

        $this->loadPaginationIntegration($config, $container);
    }

    protected function loadPhpcrPersistence($config, XmlFileLoader $loader, ContainerBuilder $container)
    {
        $container->setParameter($this->getAlias().'.blog_basepath', $config['persistence']['phpcr']['blog_basepath']);

        foreach ($config['persistence']['phpcr']['class'] as $type => $classFqn) {
            $container->setParameter(
                $param = sprintf('cmf_blog.phpcr.%s.class', $type),
                $classFqn
            );
        }

        $loader->load('initializer-phpcr.xml');
        $loader->load('doctrine-phpcr.xml');
    }

    protected function loadSonataAdmin(array $config, XmlFileLoader $loader, ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');
        if (!isset($bundles['SonataDoctrinePHPCRAdminBundle'])) {
            return;
        }

        $loader->load('admin.xml');
    }

    protected function loadMenuIntegration(array $config, XmlFileLoader $loader, ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');
        if (!isset($bundles['CmfMenuBundle'])) {
            return;
        }

        if (empty($config['integrate_menu']['content_key'])) {
            if (!class_exists('Symfony\\Cmf\\Bundle\\RoutingBundle\\Routing\\DynamicRouter')) {
                throw new \RuntimeException('You need to set the content_key when not using the CmfRoutingBundle DynamicRouter');
            }
            $contentKey = DynamicRouter::CONTENT_KEY;
        } else {
            $contentKey = $config['integrate_menu']['content_key'];
        }

        $container->setParameter('cmf_blog.content_key', $contentKey);

        $loader->load('menu.xml');
    }

    protected function loadPaginationIntegration(array $config, ContainerBuilder $container)
    {
        if (isset($config['pagination']) && $config['pagination']['enabled']) {
            $container->setParameter($this->getAlias().'.pagination.enabled', true);
            $container->setParameter($this->getAlias().'.pagination.posts_per_page', $config['pagination']['posts_per_page']);
        } else {
            // this parameter is used in the cmf_blog.blog_controller service definition, so
            // it must be defined until it's a viable option to use the expression language instead
            $container->setParameter($this->getAlias().'.pagination.posts_per_page', 0);
        }
    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     */
    public function getXsdValidationBasePath()
    {
        return __DIR__.'/../Resources/config/schema';
    }

    public function getNamespace()
    {
        return 'http://cmf.symfony.com/schema/dic/cmf_blog';
    }
}
