<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 09/02/2016
 * Time: 15:51
 */

namespace BiometricSite\Service;


use BiometricSite\Controller\LoginAuthControllerInterface;
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
     * @param LoginAuthControllerInterface $endpoint
     * @return bool
     */
    public function authenticateUser($username, $password, LoginAuthControllerInterface $endpoint) {
        $user = $this->userRepository->findByUsername($username);
        if (!$user) {
            return $endpoint->unsuccessfulLogin();
        }

        if (!password_verify($password, $user->password)) {
            return $endpoint->unsuccessfulLogin();
        }

        if (!$this->userRepository->isBiometricallyAuthenticated($user->user_id)) {
            return $endpoint->unsuccessfulLogin();
        }

        $previousSessions = $this->userRepository->getBiometricSessions($user->user_id);

        return $endpoint->successfulLogin($previousSessions);
    }

} 