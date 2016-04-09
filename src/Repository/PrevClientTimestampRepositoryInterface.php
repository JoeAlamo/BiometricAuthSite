<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 17:32
 */

namespace BiometricSite\Repository;


interface PrevClientTimestampRepositoryInterface {

    /**
     * @param $biometric_client_id
     * @param $timestamp
     * @return bool
     */
    public function addOrUpdate($biometric_client_id, $timestamp);

    /**
     * @param int $biometric_client_id
     * @return int $timestamp|false
     */
    public function find($biometric_client_id);

    /**
     * @param $biometric_client_id
     * @param $timestamp
     * @return bool
     */
    public function isFresh($timestamp);
} 