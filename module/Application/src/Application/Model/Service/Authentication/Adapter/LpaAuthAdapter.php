<?php

namespace Application\Model\Service\Authentication\Adapter;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\Authentication\Identity\User;
use Zend\Authentication\Adapter\Exception\RuntimeException;
use Zend\Authentication\Result;
use DateTime;

/**
 * Performs email address & password authentication with the LPA API Client.
 *
 * @package Application\Model\Service\Authentication\Adapter
 */
class LpaAuthAdapter implements AdapterInterface
{
    private $client;
    private $email;
    private $password;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Set the email address credential to attempt authentication with.
     *
     * @param $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = trim(strtolower($email));

        return $this;
    }

    /**
     * Set the password credential to attempt authentication with.
     *
     * @param $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Attempt to authenticate the user with the set credentials, via the LPA API Client.
     *
     * @return Result
     */
    public function authenticate()
    {
        if (!isset($this->email)) {
            throw new RuntimeException('Email address not set');
        }

        if (!isset($this->password)) {
            throw new RuntimeException('Password not set');
        }

        $response = $this->client->authenticate($this->email, $this->password);

        // Don't leave this lying around
        unset($this->password);

        if (!$response->isAuthenticated()) {
            return new Result(Result::FAILURE, null, [
                $response->getErrorDescription()
            ]);
        }

        $lastLogin = new DateTime($response->getLastLogin());
        $identity = new User($response->getUserId(), $response->getToken(), $response->getExpiresIn(), $lastLogin);

        $messages = [];

        //  If inactivity flags were cleared during this authentication then put a message in the result
        if ($response->getInactivityFlagsCleared()) {
            $messages[] = 'inactivity-flags-cleared';
        }

        return new Result(Result::SUCCESS, $identity, $messages);
    }
}