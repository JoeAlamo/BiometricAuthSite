<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 05/04/2016
 * Time: 17:18
 */

namespace BiometricSite\Controller;

use Symfony\Component\HttpFoundation\Response;

abstract class AbstractBioAuthController {

    public function invalidClientIdResponse() {
        return new Response('', Response::HTTP_FORBIDDEN);
    }

    public function invalidRequestResponse() {
        return new Response('', Response::HTTP_BAD_REQUEST);
    }

    public function invalidClientMACResponse() {
        return new Response('', Response::HTTP_FORBIDDEN);
    }

    public function invalidClientRandomResponse() {
        return new Response('', Response::HTTP_FORBIDDEN);
    }

    public function invalidSessionIdResponse() {
        return new Response('', Response::HTTP_NOT_FOUND);
    }

    public function invalidTimestampResponse() {
        return new Response('', Response::HTTP_FORBIDDEN);
    }

    public function invalidTagResponse() {
        return new Response('', Response::HTTP_FORBIDDEN);
    }

} 