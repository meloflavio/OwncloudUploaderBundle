<?php

namespace Cds\OnwcloudUploaderBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpKernel\Kernel;

class Configuration
{


    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        if (Kernel::VERSION_ID >= 40200) {
            $builder = new TreeBuilder('vich_uploader');
            $rootNode = $builder->getRootNode();
        } else {
            $builder = new TreeBuilder();
            $rootNode = $builder->root('cds_uploader');
        }

        $rootNode
            ->children()
            ->scalarNode('OWNCLOUD_URL')->isRequired()->end()
            ->scalarNode('OWNCLOUD_USER')->isRequired()->end()
            ->scalarNode('OWNCLOUD_PASSWORD')->isRequired()->end()
            ->end();

        return $builder;
    }

}