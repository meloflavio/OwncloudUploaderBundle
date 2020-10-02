<?php

namespace MeloFlavio\OwncloudUploaderBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpKernel\Kernel;

class Configuration implements ConfigurationInterface
{


    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        if (Kernel::VERSION_ID >= 40200) {
            $builder = new TreeBuilder('melo_flavio_owncloud_uploader');
            $rootNode = $builder->getRootNode();
        } else {
            $builder = new TreeBuilder();
            $rootNode = $builder->root('melo_flavio_owncloud_uploader');
        }

        $rootNode
            ->children()
            ->scalarNode('OWNCLOUD_URL')->end()
            ->scalarNode('OWNCLOUD_USER')->end()
            ->scalarNode('OWNCLOUD_PASSWORD')->end()
            ->end();

        return $builder;
    }

}