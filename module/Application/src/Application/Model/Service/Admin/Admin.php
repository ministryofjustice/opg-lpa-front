<?php

namespace Application\Model\Service\Admin;

use Application\Model\Service\AbstractService;
use Application\Model\Service\ApiClient\Client as ApiClient;
use Application\Model\Service\ApiClient\Exception\ResponseException;
use DateTime;
use DateTimeZone;

class Admin extends AbstractService
{
    private $client;

    /**
     * @param ApiClient $client
     */
    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $email
     * @return array|bool
     */
    public function searchUsers(string $email)
    {
        $result = $this->client->searchUsers($email);

        if ($result !== false) {
            $result = $this->parseDateTime($result, 'lastLoginAt');
            $result = $this->parseDateTime($result, 'updatedAt');
            $result = $this->parseDateTime($result, 'createdAt');
            $result = $this->parseDateTime($result, 'activatedAt');
            $result = $this->parseDateTime($result, 'deletedAt');

            if (array_key_exists('userId', $result) && $result['isActive'] === true) {
                $result['numberOfLpas'] = $this->client->getApplicationCount($result['userId']);
            }
        }

        return $result;
    }

    /**
     * @param array $result
     * @param string $key
     * @return array
     */
    private function parseDateTime(array $result, string $key)
    {
        if (array_key_exists($key, $result) && $result[$key] !== false) {
            $result[$key] = new DateTime($result[$key]['date'], new DateTimeZone($result[$key]['timezone']));
        }
        return $result;
    }
}