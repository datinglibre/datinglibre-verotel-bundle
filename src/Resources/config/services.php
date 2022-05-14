<?php

declare(strict_types=1);

use DatingLibre\AppApi\Subscription\SubscriptionEventServiceInterface;
use DatingLibre\VerotelBundle\Controller\VerotelPostbackController;
use DatingLibre\VerotelBundle\Command\CancelSubscription;
use DatingLibre\VerotelBundle\Service\FlexpayClientService;
use DatingLibre\VerotelBundle\Service\VerotelSubscriptionEventService;
use DatingLibre\VerotelBundle\Service\VerotelSubscriptionProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(FlexpayClientService::class)
        ->public()
        ->autowire(true)
        ->autoconfigure(true)
        ->args(['%datinglibre.verotel_shop_id%', '%env(resolve:VEROTEL_SIGNATURE_KEY)%']);

    $services->set(VerotelSubscriptionEventService::class)
        ->public()
        ->autowire(true)
        ->autoconfigure(true)
        ->args([service(FlexpayClientService::class), service(SubscriptionEventServiceInterface::class)]);

    $services->set(VerotelSubscriptionProvider::class)
        ->public()
        ->autoconfigure(true)
        ->autowire(true)
        ->args([service(FlexpayClientService::class),
            '%datinglibre.verotel_signup_active%',
            '%datinglibre.verotel_subscription_name%',
            '%datinglibre.verotel_price_amount%',
            '%datinglibre.verotel_price_currency%',
            '%datinglibre.verotel_subscription_period%'])
        ->tag('datinglibre.subscription_provider_service', []);

    $services->set(VerotelPostbackController::class)
        ->public()
        ->tag('controller.service_arguments', [])
        ->tag('container.service_subscriber', [])
        ->autowire(true)
        ->autoconfigure(true)
        ->args([service('monolog.logger'), service(VerotelSubscriptionEventService::class)])
        ->call('setContainer', [service('service_container')]);

    $services->set(CancelSubscription::class)
        ->tag('console.command', [])
        ->tag('container.no_preload', [])
        ->autowire(true)
        ->autoconfigure(true)
        ->args([service(FlexpayClientService::class)])
        ->call('setName', ['app:verotel:cancel']);
};
