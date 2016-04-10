<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/04/2016
 * Time: 14:59
 */

namespace BiometricSite\Controller;


use BiometricSite\Service\FailedVerificationServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FailedVerificationController implements FailedVerificationControllerInterface {
    private $request;
    private $failedVerificationService;

    public function __construct(Request $request, FailedVerificationServiceInterface $failedVerificationService) {
        $this->request = $request;
        $this->failedVerificationService = $failedVerificationService;
    }

    public function logFailedVerificationAction() {
        return $this->failedVerificationService->logFailedVerificationAttempt(
            $this->request->get('client_id'),
            $this->request->getClientIp(),
            $this
        );
    }

    public function successfullyLoggedResponse()
    {
        return new Response('', Response::HTTP_CREATED);
    }

    public function invalidClientIdResponse()
    {
        return new Response('', Response::HTTP_FORBIDDEN);
    }
}