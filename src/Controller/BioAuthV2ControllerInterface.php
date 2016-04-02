<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 29/03/2016
 * Time: 18:43
 */

namespace BiometricSite\Controller;


interface BioAuthV2ControllerInterface {
    public function stage1Action();

    public function stage1SuccessResponse($session_id, $server_id);

    public function stage2Action($session_id);

    public function stage2SuccessResponse($server_mac, $duration);

    public function invalidClientIdResponse();

    public function invalidRequestResponse();

    public function invalidClientMACResponse();

    public function invalidSessionIdResponse();

} 