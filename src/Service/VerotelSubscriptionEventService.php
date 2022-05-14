<?php

namespace DatingLibre\VerotelBundle\Service;

use DateTimeImmutable;
use DateTimeInterface;
use DatingLibre\AppApi\Subscription\ActiveSubscriptionExistsException;
use DatingLibre\AppApi\Subscription\MissingUserParameterError;
use DatingLibre\AppApi\Subscription\SubscriptionEventServiceInterface;
use DatingLibre\AppApi\Subscription\SubscriptionNotFoundException;
use DatingLibre\AppApi\Subscription\UnrecognizedEventError;
use DatingLibre\AppApi\Subscription\UserNotFoundException;
use DatingLibre\AppApi\Subscription\UserParameterMissingException;
use Exception;
use Symfony\Component\Uid\Uuid;

class VerotelSubscriptionEventService
{
    private const DATE_FORMAT = 'Y-m-d';

    public const VEROTEL = 'verotel';
    public const SIGNATURE_KEY = 'signature';
    public const INITIAL_EVENT_TYPE = 'initial';
    public const USER_ID_KEY = 'custom1';
    public const EVENT_TYPE_KEY = 'event';
    public const NEXT_CHARGE_ON_KEY = 'nextChargeOn';
    public const SALE_ID_KEY = 'saleID';
    public const CANCEL = 'cancel';
    public const REFERENCE_ID_KEY = 'referenceID';
    public const CC_BRAND_KEY = 'CCBrand';
    public const CUSTOM_1_KEY = 'custom1';
    public const EVENT_KEY = 'event';
    public const PAYMENT_METHOD_KEY = 'paymentMethod';
    public const PERIOD_KEY = 'period';
    public const AMOUNT_KEY = 'amount';
    public const PRICE_AMOUNT_KEY = 'priceAmount';
    public const PRICE_CURRENCY_KEY = 'priceCurrency';
    public const SHOP_ID_KEY = 'shopID';
    public const SUBSCRIPTION_TYPE_KEY = 'subscriptionType';
    public const TRANSACTION_ID_KEY = 'transactionID';
    public const TRUNCATED_PAN_KEY = 'truncatedPAN';
    public const CANCELLED_BY_KEY = 'cancelledBy';
    public const TYPE_KEY = 'type';
    public const REBILL_EVENT_TYPE = 'rebill';
    public const SUBSCRIPTION_PHASE_KEY = 'subscriptionPhase';
    public const EXPIRY = 'expiry';
    public const CHARGEBACK = 'chargeback';
    public const PARENT_ID_KEY = 'parentID';

    private FlexpayClientService $flexpayClientService;
    private SubscriptionEventServiceInterface $subscriptionEventService;

    public function __construct(
        FlexpayClientService $flexpayClientService,
        SubscriptionEventServiceInterface $subscriptionEventService
    ) {
        $this->flexpayClientService = $flexpayClientService;
        $this->subscriptionEventService = $subscriptionEventService;
    }

    /**
     * @throws ActiveSubscriptionExistsException
     * @throws UserNotFoundException
     * @throws UserParameterMissingException
     * @throws Exception
     */
    public function processEvent(array $data): void
    {
        try {
            switch ($data[self::EVENT_TYPE_KEY]) {
                case self::INITIAL_EVENT_TYPE:
                    $this->create($data);
                    break;
                case self::REBILL_EVENT_TYPE:
                    $this->renew($data);
                    break;
                case self::CANCEL:
                    $this->cancel($data);
                    break;
                case self::EXPIRY:
                    $this->expire($data);
                    break;
                case self::CHARGEBACK:
                    $this->chargeback($data);
                    break;
                default:
                    $this->subscriptionEventService->error(new UnrecognizedEventError(self::VEROTEL, json_encode($data)));
            }
        } catch (UserParameterMissingException $e) {
            $this->subscriptionEventService->error(new MissingUserParameterError(
                self::VEROTEL,
                $data[self::SALE_ID_KEY],
                [
                    self::SALE_ID_KEY => $data[self::SALE_ID_KEY],
                    self::TRANSACTION_ID_KEY => $data[self::TRANSACTION_ID_KEY],
                    self::PAYMENT_METHOD_KEY => $data[self::PAYMENT_METHOD_KEY],
                    self::CC_BRAND_KEY => $data[self::CC_BRAND_KEY],
                    self::USER_ID_KEY => $e->getMessage()
                ]
            ));

            throw $e;
        }
    }

    /**
     * @throws ActiveSubscriptionExistsException
     * @throws UserNotFoundException
     * @throws UserParameterMissingException
     */
    private function create(array $data): void
    {
        $this->subscriptionEventService->create(
            $this->requireUserId($data[self::USER_ID_KEY]),
            self::VEROTEL,
            $data[self::SALE_ID_KEY],
            $this->parseDate($data[self::NEXT_CHARGE_ON_KEY]),
            $this->parseDate($data[self::NEXT_CHARGE_ON_KEY]),
            [
                self::SALE_ID_KEY => $data[self::SALE_ID_KEY],
                self::TRANSACTION_ID_KEY => $data[self::TRANSACTION_ID_KEY],
                self::PAYMENT_METHOD_KEY => $data[self::PAYMENT_METHOD_KEY],
                self::CC_BRAND_KEY => $data[self::CC_BRAND_KEY],
                self::USER_ID_KEY => $data[self::USER_ID_KEY]
            ]
        );
    }

    /**
     * @throws UserNotFoundException
     * @throws SubscriptionNotFoundException
     */
    private function renew(array $data): void
    {
        $this->subscriptionEventService->renew(
            self::VEROTEL,
            $data[self::SALE_ID_KEY],
            $this->parseDate($data[self::NEXT_CHARGE_ON_KEY]),
            $this->parseDate($data[self::NEXT_CHARGE_ON_KEY]),
            [
                self::AMOUNT_KEY => $data[self::AMOUNT_KEY],
                self::TRANSACTION_ID_KEY => $data[self::TRANSACTION_ID_KEY],
                self::SALE_ID_KEY => $data[self::SALE_ID_KEY],
                self::CUSTOM_1_KEY => $data[self::CUSTOM_1_KEY],
                self::PAYMENT_METHOD_KEY => $data[self::PAYMENT_METHOD_KEY],
                self::CC_BRAND_KEY => $data[self::CC_BRAND_KEY]
            ]
        );
    }

    private function cancel(array $data): void
    {
        $this->subscriptionEventService->cancel(self::VEROTEL, $data[self::SALE_ID_KEY], [
            self::CANCELLED_BY_KEY => $data[self::CANCELLED_BY_KEY],
            self::SUBSCRIPTION_PHASE_KEY => $data[self::SUBSCRIPTION_PHASE_KEY],
            self::SUBSCRIPTION_TYPE_KEY => $data[self::SUBSCRIPTION_TYPE_KEY]
        ]);
    }

    private function expire(array $data): void
    {
        $this->subscriptionEventService->expire(
            self::VEROTEL,
            $data[self::SALE_ID_KEY],
            [
                self::SALE_ID_KEY => $data[self::SALE_ID_KEY],
                self::SUBSCRIPTION_TYPE_KEY => $data[self::SUBSCRIPTION_TYPE_KEY]
            ]
        );
    }

    private function chargeback(array $data)
    {
        $this->subscriptionEventService->chargeback(
            self::VEROTEL,
            $data[self::SALE_ID_KEY],
            [
                self::SALE_ID_KEY => $data[self::SALE_ID_KEY],
                self::SUBSCRIPTION_TYPE_KEY => $data[self::SUBSCRIPTION_TYPE_KEY],
                self::SUBSCRIPTION_PHASE_KEY => $data[self::SUBSCRIPTION_PHASE_KEY],
                self::CUSTOM_1_KEY => $data[self::CUSTOM_1_KEY],
                self::TRANSACTION_ID_KEY => $data[self::TRANSACTION_ID_KEY],
                self::PARENT_ID_KEY => $data[self::PARENT_ID_KEY]
            ]
        );
    }

    public function verify(array $data): bool
    {
        if (!array_key_exists(self::SIGNATURE_KEY, $data)) {
            return false;
        }

        return $this->flexpayClientService->validateSignature($data);
    }

    /**
     * @throws UserParameterMissingException
     */
    private function requireUserId(?string $userId): Uuid
    {
        if ($userId === null || !Uuid::isValid($userId)) {
            throw new UserParameterMissingException(sprintf('Invalid userId [%s]', $userId));
        }

        return Uuid::fromString($userId);
    }

    private function parseDate(string $date): ?DateTimeInterface
    {
        $parsedDate = DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $date);

        return $parsedDate === false ? null : $parsedDate;
    }
}
