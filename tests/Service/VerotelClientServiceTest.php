<?php

declare(strict_types=1);

namespace DatingLibre\VerotelBundle\Tests\Service;

use DatingLibre\AppApi\Subscription\SubscriptionEventServiceInterface;
use DatingLibre\VerotelBundle\Service\FlexpayClientService;
use DatingLibre\VerotelBundle\Service\VerotelSubscriptionEventService;
use PHPUnit\Framework\TestCase;

class VerotelClientServiceTest extends TestCase
{
    private VerotelSubscriptionEventService $subscriptionEventService;

    public function setUp(): void
    {
        $subscriptionEventServiceMock = $this->getMockBuilder(SubscriptionEventServiceInterface::class)
            ->getMock();

        $this->subscriptionEventService = new VerotelSubscriptionEventService(
            new FlexpayClientService('123456', 'abcd1234'),
            $subscriptionEventServiceMock
        );
    }

    public function testFailsVerificationIfSignatureNotPresent(): void
    {
        $this->assertFalse($this->subscriptionEventService->verify([]));
    }
}
