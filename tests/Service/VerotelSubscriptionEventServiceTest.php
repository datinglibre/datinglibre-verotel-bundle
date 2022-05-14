<?php

declare(strict_types=1);

namespace DatingLibre\VerotelBundle\Tests\Service;

use DateTimeImmutable;
use DateTimeInterface;
use DatingLibre\AppApi\Subscription\ActiveSubscriptionExistsException;
use DatingLibre\AppApi\Subscription\SubscriptionEventServiceInterface;
use DatingLibre\AppApi\Subscription\UnrecognizedEventError;
use DatingLibre\AppApi\Subscription\UserNotFoundException;
use DatingLibre\VerotelBundle\Service\FlexpayClientService;
use DatingLibre\VerotelBundle\Service\VerotelSubscriptionEventService;
use DatingLibre\AppApi\Subscription\UserParameterMissingException;
use PHPUnit\Framework\TestCase;

class VerotelSubscriptionEventServiceTest extends TestCase
{
    private const TEST_USER_ID = '1ec29b15-72bd-67c8-80ad-79f6dc3afa9e';
    private const TEST_SALE_ID = '456789';
    private const TEST_NEXT_CHARGE_ON = '2022-01-18';
    private const TEST_CANCELLED_BY = 'user';
    private const TEST_SUBSCRIPTION_PHASE = 'normal';
    private const TEST_SUBSCRIPTION_TYPE = 'recurring';
    private const TEST_PARENT_ID = '10000';
    private DateTimeInterface $testNextDateTime;
    private const TEST_REFERENCE_ID = '0592094950';
    private const TEST_TRANSACTION_ID = '99999991';
    private const TEST_PAYMENT_METHOD = 'CC';
    private const TEST_CC_BRAND = 'VISA';
    private const TEST_AMOUNT = "12.31";

    private VerotelSubscriptionEventService $verotelSubscriptionEventService;
    private SubscriptionEventServiceInterface $subscriptionEventServiceMock;
    private FlexpayClientService $flexpayClientServiceMock;

    public function setUp(): void
    {
        $this->testNextDateTime = DateTimeImmutable::createFromFormat('Y-m-d', self::TEST_NEXT_CHARGE_ON);

        $this->subscriptionEventServiceMock = $this->getMockBuilder(SubscriptionEventServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->flexpayClientServiceMock = $this->getMockBuilder(FlexpayClientService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->verotelSubscriptionEventService = new VerotelSubscriptionEventService(
            $this->flexpayClientServiceMock,
            $this->subscriptionEventServiceMock
        );
    }

    /**
     * @throws ActiveSubscriptionExistsException
     * @throws UserNotFoundException
     * @throws UserParameterMissingException
     */
    public function testProcessesSuccessEvent(): void
    {
        $this->subscriptionEventServiceMock->expects($this->once())
            ->method('create')
            ->with(
                self::TEST_USER_ID,
                VerotelSubscriptionEventService::VEROTEL,
                self::TEST_SALE_ID,
                $this->testNextDateTime,
                $this->testNextDateTime,
                [
                    VerotelSubscriptionEventService::SALE_ID_KEY => self::TEST_SALE_ID,
                    VerotelSubscriptionEventService::TRANSACTION_ID_KEY => self::TEST_TRANSACTION_ID,
                    VerotelSubscriptionEventService::PAYMENT_METHOD_KEY => self::TEST_PAYMENT_METHOD,
                    VerotelSubscriptionEventService::CC_BRAND_KEY => self::TEST_CC_BRAND,
                    VerotelSubscriptionEventService::USER_ID_KEY => self::TEST_USER_ID
                ]
            );

        $this->verotelSubscriptionEventService->processEvent([
            VerotelSubscriptionEventService::CC_BRAND_KEY => self::TEST_CC_BRAND,
            VerotelSubscriptionEventService::CUSTOM_1_KEY => self::TEST_USER_ID,
            VerotelSubscriptionEventService::EVENT_KEY => 'initial',
            VerotelSubscriptionEventService::NEXT_CHARGE_ON_KEY => self::TEST_NEXT_CHARGE_ON,
            VerotelSubscriptionEventService::PAYMENT_METHOD_KEY => self::TEST_PAYMENT_METHOD,
            VerotelSubscriptionEventService::PERIOD_KEY => 'P1M',
            VerotelSubscriptionEventService::PRICE_AMOUNT_KEY => '12.31',
            VerotelSubscriptionEventService::PRICE_CURRENCY_KEY => 'EUR',
            VerotelSubscriptionEventService::SALE_ID_KEY => self::TEST_SALE_ID,
            VerotelSubscriptionEventService::SHOP_ID_KEY => '122965',
            VerotelSubscriptionEventService::SUBSCRIPTION_TYPE_KEY => 'recurring',
            VerotelSubscriptionEventService::TRANSACTION_ID_KEY => self::TEST_TRANSACTION_ID,
            VerotelSubscriptionEventService::TRUNCATED_PAN_KEY => 'XXXXXXXXXXXX1234',
            VerotelSubscriptionEventService::TYPE_KEY => 'subscription',
            VerotelSubscriptionEventService::SIGNATURE_KEY => 'bf0bdcbc38af48499e86a5fd9dbcb63a1a6bff1b'
        ]);
    }

    public function testProcessesRebillEvent(): void
    {
        $this->subscriptionEventServiceMock->expects($this->once())
            ->method('renew')
            ->with(
                VerotelSubscriptionEventService::VEROTEL,
                self::TEST_SALE_ID,
                $this->testNextDateTime,
                $this->testNextDateTime,
                [
                    VerotelSubscriptionEventService::SALE_ID_KEY => self::TEST_SALE_ID,
                    VerotelSubscriptionEventService::TRANSACTION_ID_KEY => self::TEST_TRANSACTION_ID,
                    VerotelSubscriptionEventService::PAYMENT_METHOD_KEY => self::TEST_PAYMENT_METHOD,
                    VerotelSubscriptionEventService::CC_BRAND_KEY => self::TEST_CC_BRAND,
                    VerotelSubscriptionEventService::USER_ID_KEY => self::TEST_USER_ID,
                    VerotelSubscriptionEventService::AMOUNT_KEY => self::TEST_AMOUNT
                ]
            );

        $this->verotelSubscriptionEventService->processEvent([
            VerotelSubscriptionEventService::EVENT_KEY => VerotelSubscriptionEventService::REBILL_EVENT_TYPE,
            VerotelSubscriptionEventService::TRANSACTION_ID_KEY => self::TEST_TRANSACTION_ID,
            VerotelSubscriptionEventService::SALE_ID_KEY => self::TEST_SALE_ID,
            VerotelSubscriptionEventService::REFERENCE_ID_KEY => self::TEST_REFERENCE_ID,
            VerotelSubscriptionEventService::NEXT_CHARGE_ON_KEY => self::TEST_NEXT_CHARGE_ON,
            VerotelSubscriptionEventService::CC_BRAND_KEY => self::TEST_CC_BRAND,
            VerotelSubscriptionEventService::USER_ID_KEY => self::TEST_USER_ID,
            VerotelSubscriptionEventService::PAYMENT_METHOD_KEY => self::TEST_PAYMENT_METHOD,
            VerotelSubscriptionEventService::AMOUNT_KEY => self::TEST_AMOUNT
        ]);
    }

    public function testProcessesCancelEvent(): void
    {
        $this->subscriptionEventServiceMock->expects($this->once())
            ->method('cancel')
            ->with(
                VerotelSubscriptionEventService::VEROTEL,
                self::TEST_SALE_ID,
                [
                    VerotelSubscriptionEventService::CANCELLED_BY_KEY => self::TEST_CANCELLED_BY,
                    VerotelSubscriptionEventService::SUBSCRIPTION_PHASE_KEY => self::TEST_SUBSCRIPTION_PHASE,
                    VerotelSubscriptionEventService::SUBSCRIPTION_TYPE_KEY => self::TEST_SUBSCRIPTION_TYPE
                ]
            );

        $this->verotelSubscriptionEventService->processEvent([
            VerotelSubscriptionEventService::EVENT_KEY => VerotelSubscriptionEventService::CANCEL,
            VerotelSubscriptionEventService::SALE_ID_KEY => self::TEST_SALE_ID,
            VerotelSubscriptionEventService::CANCELLED_BY_KEY => self::TEST_CANCELLED_BY,
            VerotelSubscriptionEventService::SUBSCRIPTION_PHASE_KEY => self::TEST_SUBSCRIPTION_PHASE,
            VerotelSubscriptionEventService::SUBSCRIPTION_TYPE_KEY => self::TEST_SUBSCRIPTION_TYPE
        ]);
    }

    public function testProcessesExpiryEvent(): void
    {
        $this->subscriptionEventServiceMock->expects($this->once())
            ->method('expire')
            ->with(
                VerotelSubscriptionEventService::VEROTEL,
                self::TEST_SALE_ID,
                [
                    VerotelSubscriptionEventService::SALE_ID_KEY => self::TEST_SALE_ID,
                    VerotelSubscriptionEventService::SUBSCRIPTION_TYPE_KEY => self::TEST_SUBSCRIPTION_TYPE
                ]
            );

        $this->verotelSubscriptionEventService->processEvent([
            VerotelSubscriptionEventService::EVENT_KEY => VerotelSubscriptionEventService::EXPIRY,
            VerotelSubscriptionEventService::SALE_ID_KEY => self::TEST_SALE_ID,
            VerotelSubscriptionEventService::SUBSCRIPTION_TYPE_KEY => self::TEST_SUBSCRIPTION_TYPE
        ]);
    }

    public function testProcessesChargebackEvent(): void
    {
        $this->subscriptionEventServiceMock->expects($this->once())
            ->method('chargeback')
            ->with(
                VerotelSubscriptionEventService::VEROTEL,
                self::TEST_SALE_ID,
                [
                    VerotelSubscriptionEventService::SALE_ID_KEY => self::TEST_SALE_ID,
                    VerotelSubscriptionEventService::SUBSCRIPTION_TYPE_KEY => self::TEST_SUBSCRIPTION_TYPE,
                    VerotelSubscriptionEventService::TRANSACTION_ID_KEY => self::TEST_TRANSACTION_ID,
                    VerotelSubscriptionEventService::CUSTOM_1_KEY => self::TEST_USER_ID,
                    VerotelSubscriptionEventService::PARENT_ID_KEY => self::TEST_PARENT_ID,
                    VerotelSubscriptionEventService::SUBSCRIPTION_PHASE_KEY => self::TEST_SUBSCRIPTION_PHASE
                ]
            );

        $this->verotelSubscriptionEventService->processEvent([
            VerotelSubscriptionEventService::SALE_ID_KEY => self::TEST_SALE_ID,
            VerotelSubscriptionEventService::SUBSCRIPTION_TYPE_KEY => self::TEST_SUBSCRIPTION_TYPE,
            VerotelSubscriptionEventService::EVENT_KEY => VerotelSubscriptionEventService::CHARGEBACK,
            VerotelSubscriptionEventService::REFERENCE_ID_KEY => self::TEST_REFERENCE_ID,
            VerotelSubscriptionEventService::TRANSACTION_ID_KEY => self::TEST_TRANSACTION_ID,
            VerotelSubscriptionEventService::PARENT_ID_KEY => self::TEST_PARENT_ID,
            VerotelSubscriptionEventService::CUSTOM_1_KEY => self::TEST_USER_ID,
            VerotelSubscriptionEventService::SUBSCRIPTION_PHASE_KEY => self::TEST_SUBSCRIPTION_PHASE
        ]);
    }

    public function testRaisesUnrecognizedEvent(): void
    {
        $payload = [
            VerotelSubscriptionEventService::SALE_ID_KEY => self::TEST_SALE_ID,
            VerotelSubscriptionEventService::EVENT_KEY => 'unknown'
        ];

        $this->subscriptionEventServiceMock->expects($this->once())
            ->method('error')
            ->with(
                new UnrecognizedEventError(
                    VerotelSubscriptionEventService::VEROTEL,
                    json_encode($payload)
                )
            );

        $this->verotelSubscriptionEventService->processEvent($payload);
    }
}
