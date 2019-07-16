<?php

namespace Orca\UserLogBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\Processor;
/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class OrcaUserLogExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);
        $container->setParameter('orca_user_log.userlog_entity', $config['userlog_entity']);
        $container->setParameter('orca_user_log.userlog_repository', $config['userlog_repository']);
        $container->setParameter('orca_user_log.table_name', $config['table_name']);
        $container->setParameter('orca_user_log.user_class', $config['user_class']);
        $container->setParameter('orca_user_log.exclude_uri', $config['exclude_uri']);
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');


//        $loader2 = new Loader\XmlFileLoader($container, new FileLocator(array(__DIR__.'/../Resources/config')));
//        $loader2->load('g_chart.xml');
    }
}
