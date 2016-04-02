<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 09/02/2016
 * Time: 12:10
 */

namespace BiometricSite\Controller;


use BiometricSite\Service\BioAuthV2ServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BioAuthV2Controller implements BioAuthV2ControllerInterface {
    private $request;
    private $bioAuthService;

    public function __construct(Request $request, BioAuthV2ServiceInterface $bioAuthService) {
        $this->request = $request;
        $this->bioAuthService = $bioAuthService;
    }

    public function stage1Action()
    {
        return $this->bioAuthService->performStage1(
            $this->request->getClientIp(),
            $this
        );
    }

    public function stage1SuccessResponse($session_id, $server_id)
    {
        $responseData = [
            'session_id' => $session_id,
            'server_id' => $server_id
        ];

        return new JsonResponse((object)$responseData, Response::HTTP_CREATED);
    }

    public function stage2Action($session_id)
    {
        // TODO: Implement stage2Action() method.
    }

    public function stage2SuccessResponse($server_mac, $duration)
    {
        // TODO: Implement stage2SuccessResponse() method.
    }

    public function invalidClientIdResponse() {
        return new Response('', Response::HTTP_FORBIDDEN);
    }

    public function invalidRequestResponse() {
        return new Response('', Response::HTTP_BAD_REQUEST);
    }

    public function invalidClientMACResponse()
    {
        return new Response('', Response::HTTP_FORBIDDEN);
    }

    public function invalidSessionIdResponse()
    {
        return new Response('', Response::HTTP_NOT_FOUND);
    }
}