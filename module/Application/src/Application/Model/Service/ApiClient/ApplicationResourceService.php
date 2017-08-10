<?php

namespace Application\Model\Service\ApiClient;

use GuzzleHttp\Client as GuzzleClient;
use Exception;

class ApplicationResourceService
{
    private $apiClient;
    private $endpoint;
    private $resourceType;

    /**
     * @param $lpaId
     * @param $resourceType
     * @param Client $apiClient
     */
    public function __construct($lpaId, $resourceType, Client $apiClient)
    {
        $this->apiClient = $apiClient;
        $this->resourceType = $resourceType;
        $this->endpoint = $apiClient->getApiBaseUri() . '/v1/users/' . $this->apiClient->getUserId() . '/applications/' . $lpaId . '/' . $resourceType;
    }

    /**
     * Return the API response for getting the resource of the given type
     *
     * If property not yet set, return null
     * If error, return false
     */
    public function getResource()
    {
        $response = $this->httpClient()->get($this->endpoint, [
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $code = $response->getStatusCode();

        if ($code == 204) {
            return null; // not yet set
        }

        if ($code != 200) {
            $this->apiClient->log($response, false);
            return false;
        }

        return $response;
    }

    /**
     * Get list of resources for the current user
     * Combine pages, if necessary
     */
    public function getResourceList($entityClass)
    {
        $resourceList = array();

        do {
            $response = $this->httpClient()->get($this->endpoint);

            $json = $response->json();

            if (!isset($json['_links']) || !isset($json['count'])) {
                $this->apiClient->log($response, false);
                return false;
            }

            if ($json['count'] == 0) {
                return [];
            }

            if (!isset($json['_embedded'][$this->resourceType])) {
                $this->apiClient->log($response, false);
                return false;
            }

            foreach ($json['_embedded'][$this->resourceType] as $singleResourceJson) {
                //  If this is an attorney then determine which type
                if ($entityClass == '\Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney') {
                    switch ($singleResourceJson['type']) {
                        case 'human':
                            $entityClass = '\Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human';
                            break;
                        case 'trust':
                            $entityClass = '\Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation';
                            break;
                        default:
                            throw new Exception('Invalid attorney type: ' . $singleResourceJson['type']);
                    }
                }

                $resourceList[] = new $entityClass($singleResourceJson);
            }

            if (isset($json['_links']['next']['href'])) {
                $path = $json['_links']['next']['href'];
            } else {
                $path = null;
            }
        } while (!is_null($path));

        return $resourceList;
    }

    /**
     * Return the json response for an endpoint
     *
     * @param $key The JSON key of the value being retrieved
     * @return boolean|null|mixed
     */
    public function getRawJson()
    {
        $response = $this->httpClient()->get($this->endpoint, [
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $code = $response->getStatusCode();

        if ($code == 204) {
            return null; // not yet set
        }

        if ($code != 200) {
            $this->apiClient->log($response, false);
            return false;
        }

        return $response->json();
    }

    /**
     * Set the data for the given resource. i.e. PUT
     *
     * @param string $jsonBody
     * @param $index number in series, if applicable
     * @return boolean
     */
    public function setResource($jsonBody, $index = null)
    {
        $response = $this->httpClient()->put($this->endpoint . (!is_null($index) ? '/' . $index : ''), [
            'body' => $jsonBody,
            'headers' => ['Content-Type' => 'application/json']
        ]);

        if (($response->getStatusCode() != 200) && ($response->getStatusCode() != 204)) {
            $this->apiClient->log($response, false);
            return false;
        }

        return true;
    }

    /**
     * Patch the data for the given resource. i.e. PUT
     *
     * @param string $jsonBody
     * @param $index number in series, if applicable
     * @return boolean
     */
    public function updateResource($jsonBody, $index = null)
    {
        $response = $this->httpClient()->patch($this->endpoint . (!is_null($index) ? '/' . $index : ''), [
            'body' => $jsonBody,
            'headers' => ['Content-Type' => 'application/json']
        ]);

        if ($response->getStatusCode() != 200) {
            $this->apiClient->log($response, false);
            return false;
        }

        return true;
    }

    /**
     * Add data for the given resource. i.e. POST
     *
     * @param string $jsonBody
     * @return boolean
     */
    public function addResource($jsonBody)
    {
        $response = $this->httpClient()->post($this->endpoint, [
            'body' => $jsonBody,
            'headers' => ['Content-Type' => 'application/json']
        ]);

        if ($response->getStatusCode() != 201) {
            $this->apiClient->log($response, false);
            return false;
        }

        return true;
    }

    /**
     * Delete the resource type from the LPA. i.e. DELETE
     *
     * @param $index number in series, if applicable
     * @return boolean
     */
    public function deleteResource($index = null)
    {
        $response = $this->httpClient()->delete($this->endpoint . (!is_null($index) ? '/' . $index : ''), [
            'headers' => ['Content-Type' => 'application/json']
        ]);

        if ($response->getStatusCode() != 204) {
            $this->apiClient->log($response, false);
            return false;
        }

        return true;
    }

    /**
     * Returns the GuzzleClient.
     *
     * If an authentication token is available it will be preset in the HTTP header.
     *
     * @return GuzzleClient
     */
    private function httpClient()
    {
        if (!isset($this->guzzleClient)) {
            $this->guzzleClient = new GuzzleClient();
            $this->guzzleClient->setDefaultOption('exceptions', false);
            $this->guzzleClient->setDefaultOption('allow_redirects', false);
        }

        if ($this->apiClient->getToken() != null) {
            $this->guzzleClient->setDefaultOption('headers/Token', $this->apiClient->getToken());
        }

        return $this->guzzleClient;
    }
}
