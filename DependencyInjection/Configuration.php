<?php

namespace Orca\UserLogBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {

        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('orca_user_log')
            ->children()
                ->scalarNode('userlog_entity')->defaultValue('Orca\UserLogBundle\Entity\TblUserLog')->end()
                ->scalarNode('userlog_repository')->defaultValue('OrcaUserLogBundle:TblUserLog')->end()
                ->scalarNode('table_name')->defaultValue('tbl_user_log')->end()
                ->scalarNode('user_class')->isRequired()->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
