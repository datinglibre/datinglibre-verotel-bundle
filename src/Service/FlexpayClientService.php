<?php

namespace DatingLibre\VerotelBundle\Service;

use Symfony\Component\Uid\Uuid;
use Verotel\FlexPay\Client;
use Verotel\FlexPay\Exception;

class FlexpayClientService
{
    private Client $flexpayClient;
    private const SALE_ID = 'saleID';

    /**
     * @throws Exception
     */
    public function __construct(
        string $shopId,
        string $signatureKey
    ) {
        $this->flexpayClient = $this->getClient($shopId, $signatureKey);
    }

    public function getSignupUrl(Uuid $userId, string $name, string $priceAmount, string $priceCurrency, string $period): string
    {
        return $this->flexpayClient->get_subscription_URL(
            [
                'name' => $name,
                'priceAmount' => $priceAmount,
                'priceCurrency' => $priceCurrency,
                'period' => $period,
                'subscriptionType' => 'recurring',
                'custom1' => $userId->toRfc4122()
            ]
        );
    }

    public function validateSignature(array $data): bool
    {
        return $this->flexpayClient->validate_signature($data);
    }

    public function getSignature(array $data): string
    {
        return $this->flexpayClient->get_signature($data);
    }

    public function getCancellationUrl(string $saleId): string
    {
        return $this->flexpayClient->get_cancel_subscription_URL([self::SALE_ID => $saleId]);
    }

    public function getStatusUrl(string $saleId): string
    {
        return $this->flexpayClient->get_status_URL([self::SALE_ID => $saleId]);
    }

    /**
     * @throws Exception
     */
    private function getClient(string $shopId, string $signatureKey): Client
    {
        return new Client($shopId, $signatureKey);
    }
}
