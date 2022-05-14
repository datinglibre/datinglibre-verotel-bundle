<?php

namespace DatingLibre\VerotelBundle\Controller;

use DatingLibre\AppApi\Subscription\ActiveSubscriptionExistsException;
use DatingLibre\AppApi\Subscription\UserNotFoundException;
use DatingLibre\AppApi\Subscription\UserParameterMissingException;
use DatingLibre\VerotelBundle\Service\VerotelSubscriptionEventService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class VerotelPostbackController extends AbstractController
{
    private const VERIFICATION_FAILURE = 'VERIFICATION_FAILURE';
    private const SUCCESS = 'OK';
    private const ERROR_OCCURRED = 'ERROR';
    private const ACTIVE_SUBSCRIPTION_ALREADY_EXISTS = 'ACTIVE_SUBSCRIPTION_ALREADY_EXISTS';
    private const USER_PARAMETER_MISSING = 'USER_PARAMETER_MISSING';
    private const USER_NOT_FOUND = 'USER_NOT_FOUND';
    private VerotelSubscriptionEventService $verotelSubscriptionEventService;
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
        VerotelSubscriptionEventService $verotelSubscriptionEventService
    ) {
        $this->logger = $logger;
        $this->verotelSubscriptionEventService = $verotelSubscriptionEventService;
    }

    /**
     * Should not attempt to process requests that can't be verified
     *
     * @throws Exception
     */
    public function postback(Request $request): Response
    {
        $queryParameters = $request->query->all();

        if (!$this->verotelSubscriptionEventService->verify($queryParameters)) {
            return new Response(self::VERIFICATION_FAILURE, 403);
        }

        try {
            $this->verotelSubscriptionEventService->processEvent($queryParameters);
        } catch (ActiveSubscriptionExistsException $e) {
            return new Response(self::ACTIVE_SUBSCRIPTION_ALREADY_EXISTS, 500);
        } catch (UserParameterMissingException $e) {
            return new Response(self::USER_PARAMETER_MISSING, 500);
        } catch (UserNotFoundException $e) {
            return new Response(self::USER_NOT_FOUND, 500);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return new Response(self::ERROR_OCCURRED, 500);
        }

        return new Response(self::SUCCESS, 200);
    }
}
