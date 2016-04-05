<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 29/03/2016
 * Time: 18:43
 */

namespace BiometricSite\Controller;


interface BioAuthV3ControllerInterface {
    public function stage1Action();

    public function stage1SuccessResponse($session_id, $server_id);

    public function stage2Action($session_id);

    public function stage2SuccessResponse($ciphertext, $tag);

    public function invalidClientIdResponse();

    public function invalidRequestResponse();

    public function invalidClientMACResponse();

    public function invalidClientRandomResponse();

    public function invalidSessionIdResponse();

    public function invalidTimestampResponse();

    public function invalidTagResponse();

} 