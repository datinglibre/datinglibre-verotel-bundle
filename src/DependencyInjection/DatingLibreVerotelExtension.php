<?php

declare(strict_types=1);

namespace DatingLibre\VerotelBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class DatingLibreVerotelExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $configuration = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter('datinglibre.verotel_shop_id', $configuration['shopId']);
        $container->setParameter('datinglibre.verotel_signup_active', $configuration['signupActive']);
        $container->setParameter('datinglibre.verotel_subscription_name', $configuration['subscriptionName']);
        $container->setParameter('datinglibre.verotel_price_amount', $configuration['priceAmount']);
        $container->setParameter('datinglibre.verotel_price_currency', $configuration['priceCurrency']);
        $container->setParameter('datinglibre.verotel_subscription_period', $configuration['subscriptionPeriod']);

        $loader->load('services.php');
    }
}
