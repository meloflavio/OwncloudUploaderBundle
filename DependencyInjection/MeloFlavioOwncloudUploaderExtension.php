<?php


namespace MeloFlavio\OwncloudUploaderBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class MeloFlavioOwncloudUploaderExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('melo_flavio_owncloud_uploader.owncloud_url', $config['OWNCLOUD_URL']);
        $container->setParameter('melo_flavio_owncloud_uploader.owncloud_user', $config['OWNCLOUD_USER']);
        $container->setParameter('melo_flavio_owncloud_uploader.owncloud_password', $config['OWNCLOUD_PASSWORD']);
        $container->setParameter('melo_flavio_owncloud_uploader.internal_download', $config['internal_download']);
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

    }
}