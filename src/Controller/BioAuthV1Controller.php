<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 09/02/2016
 * Time: 12:10
 */

namespace BiometricSite\Controller;


use BiometricSite\Service\BioAuthV1ServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BioAuthV1Controller extends AbstractBioAuthController implements BioAuthV1ControllerInterface {
    private $request;
    private $bioAuthService;

    public function __construct(Request $request, BioAuthV1ServiceInterface $bioAuthService) {
        $this->request = $request;
        $this->bioAuthService = $bioAuthService;
    }

    public function stage1Action() {
        return $this->bioAuthService->performStage1(
            $this->request->request->get('client_id'),
            $this->request->getClientIp(),
            $this
        );
    }

    public function successfulResponse($duration) {
        $responseData = [
            'expires' => (int)$duration > 0 ? (int)$duration : 0
        ];

        return new JsonResponse((object)$responseData, Response::HTTP_OK);
    }
} 