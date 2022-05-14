<?php

namespace DatingLibre\VerotelBundle\Service;

use DatingLibre\AppApi\Subscription\SubscriptionCancellationException;
use DatingLibre\AppApi\Subscription\SubscriptionProviderException;
use DatingLibre\AppApi\Subscription\SubscriptionProviderInterface;
use Exception;
use Symfony\Component\Uid\Uuid;
use Throwable;

class VerotelSubscriptionProvider implements SubscriptionProviderInterface
{
    private const VEROTEL_IP_ADDRESS_RANGE = 'https://www.verotel.com/static/nats/proxy-ips.txt';
    private const VEROTEL_IP_ADDRESS_RANGE_HASH = '18ea646ed5a9b594b567295b7ea46161';
    private bool $isSignupActive;
    private FlexpayClientService $flexpayClientService;
    private string $subscriptionName;
    private string $priceAmount;
    private string $priceCurrency;
    private string $subscriptionPeriod;

    public function __construct(FlexpayClientService $flexpayClientService, bool $isSignupActive, string $subscriptionName, string $priceAmount, string $priceCurrency, string $subscriptionPeriod)
    {
        $this->flexpayClientService = $flexpayClientService;
        $this->isSignupActive = $isSignupActive;
        $this->subscriptionName = $subscriptionName;
        $this->priceAmount = $priceAmount;
        $this->priceCurrency = $priceCurrency;
        $this->subscriptionPeriod = $subscriptionPeriod;
    }

    public function getName(): string
    {
        return 'verotel';
    }

    public function getSignupUrl(Uuid $userId): string
    {
        return $this->flexpayClientService->getSignupUrl(
            $userId,
            $this->subscriptionName,
            $this->priceAmount,
            $this->priceCurrency,
            $this->subscriptionPeriod
        );
    }

    public function getCancellationUrl(Uuid $userId, string $subscriptionId): string
    {
        return $this->flexpayClientService->getCancellationUrl($subscriptionId);
    }

    public function isSignupActive(): bool
    {
        return $this->isSignupActive;
    }

    /**
     * @throws SubscriptionCancellationException
     */
    public function cancel(string $subscriptionId, array $data): string
    {
        throw new SubscriptionCancellationException('Subscription can only be cancelled via Verotel control panel');
    }

    public function getStatus(string $subscriptionId): string
    {
        try {
            return file_get_contents($this->flexpayClientService->getStatusUrl($subscriptionId));
        } catch (Throwable $e) {
            throw new SubscriptionProviderException($e->getMessage());
        }
    }

    public function refund(string $subscriptionId, array $data): string
    {
        throw new SubscriptionProviderException('Not implemented. Please check with Verotel.');
    }

    /**
     * @throws Exception
     */
    public function verifyWebhookIps(): ?bool
    {
        return md5(file_get_contents(self::VEROTEL_IP_ADDRESS_RANGE)) === self::VEROTEL_IP_ADDRESS_RANGE_HASH;
    }
}
