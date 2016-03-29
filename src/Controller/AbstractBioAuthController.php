<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 29/03/2016
 * Time: 18:59
 */

namespace BiometricSite\Controller;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractBioAuthController {
    private $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function invalidClientIDResponse() {
        return new Response('', Response::HTTP_FORBIDDEN);
    }

    public function invalidClientMACResponse() {
        return new Response('', Response::HTTP_FORBIDDEN);
    }

    public function invalidRequestResponse() {
        return new Response('', Response::HTTP_BAD_REQUEST);
    }

    public function invalidSessionIDResponse() {
        return new Response('', Response::HTTP_NOT_FOUND);
    }

    public function invalidTagResponse() {
        return new Response('', Response::HTTP_FORBIDDEN);
    }

    public function invalidTimestampResponse() {
        return new Response('', Response::HTTP_FORBIDDEN);
    }

    public function sessionCreatedResponse() {
        return new Response('', Response::HTTP_CREATED);
    }

    public function successfulResponse() {
        return new Response('', Response::HTTP_OK);
    }

}