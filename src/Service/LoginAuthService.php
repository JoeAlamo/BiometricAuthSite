<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 09/02/2016
 * Time: 15:51
 */

namespace BiometricSite\Service;


use BiometricSite\Repository\UserRepositoryInterface;

class LoginAuthService implements LoginAuthServiceInterface {
    private $userRepository;

    public function __construct(UserRepositoryInterface $userRepository) {
        $this->userRepository = $userRepository;
    }

    /**
     * @desc Attempt to authenticate the user based on username and password.
     * The credentials must be correct and they must have undergone biometric
     * authentication prior to this.
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function authenticateUser($username, $password) {
        $user = $this->userRepository->findByUsername($username);
        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user->password)) {
            return false;
        }

        if (!$this->userRepository->isBiometricallyAuthenticated($user->user_id)) {
            return false;
        }

        return true;
    }

} 