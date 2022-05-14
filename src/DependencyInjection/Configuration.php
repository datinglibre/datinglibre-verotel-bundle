<?php

declare(strict_types=1);

namespace DatingLibre\VerotelBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('dating_libre_verotel');

        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('shopId')->end()
            ->booleanNode('signupActive')->end()
            ->scalarNode('subscriptionName')->end()
            ->scalarNode('priceAmount')->end()
            ->scalarNode('priceCurrency')->end()
            ->scalarNode('subscriptionPeriod')->end()
            ->end();

        return $treeBuilder;
    }
}
