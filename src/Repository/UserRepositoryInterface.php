<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 09/02/2016
 * Time: 15:53
 */

namespace BiometricSite\Repository;


interface UserRepositoryInterface {
    /**
     * @param int $id
     * @return \BiometricSite\Model\User|false
     */
    public function find($id);

    /**
     * @param string $username
     * @return \BiometricSite\Model\User|false
     */
    public function findByUsername($username);
} 