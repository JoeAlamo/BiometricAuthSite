<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 09/02/2016
 * Time: 12:10
 */

namespace BiometricSite\Controller;


use BiometricSite\Service\BioAuthV3ServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BioAuthV3Controller extends AbstractBioAuthController implements BioAuthV3ControllerInterface {
    private $request;
    private $bioAuthService;

    public function __construct(Request $request, BioAuthV3ServiceInterface $bioAuthService) {
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
        return $this->bioAuthService->performStage2(
            $session_id,
            $this->request->request->get('client_id'),
            $this->request->request->get('timestamp'),
            $this->request->request->get('ciphertext'),
            $this->request->request->get('tag'),
            $this->request->getClientIp(),
            $this
        );
    }

    public function stage2SuccessResponse($ciphertext, $tag)
    {
        $responseData = [
            'ciphertext' => $ciphertext,
            'tag' => $tag
        ];

        return new JsonResponse((object)$responseData, Response::HTTP_OK);
    }

}