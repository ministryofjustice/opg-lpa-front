<?php

namespace Application\Model\Service\ApiClient;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Request;
use Http\Adapter\Guzzle5\Client as Guzzle5Client;
use Http\Client\HttpClient as HttpClientInterface;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Psr\Http\Message\ResponseInterface;

class Client
{
    use ClientV1Trait;
    use ClientV2ApiTrait;
    use ClientV2AuthTrait;

    /**
     * @const string Current version of this client.
     * This follows Semantic Versioning (http://semver.org/)
     */
    const VERSION = '4.0.0';

    /**
     * The base URI for the API
     */
    private $apiBaseUri = 'https://apiv2';

    /**
     * The base URI for the auth server
     */
    private $authBaseUri = 'https://authv2';

    /**
     * @var HttpClientInterface PSR-7 compatible HTTP Client
     */
    private $httpClient;

    /**
     * The user ID of the logged in account
     *
     * @var string
     */
    private $userId;

    /**
     * The API auth token
     *
     * @var string
     */
    private $token;

    // Internal API access methods

    /**
     * Generates the standard set of HTTP headers expected by the API.
     *
     * @return array
     */
    private function buildHeaders()
    {
        $headers = [
            'Accept'        => 'application/json',
            'Content-type'  => 'application/json',
            'User-agent'    => 'LPA-API-PHP-CLIENT/'.self::VERSION
        ];

        if ($this->getToken() != null) {
            $headers['Token'] = $this->getToken();
        }

        return $headers;
    }

    private function httpGet(Uri $url, array $query = array())
    {
        foreach ($query as $name => $value) {
            $url = Uri::withQueryValue($url, $name, $value);
        }

        $request = new Request('GET', $url, $this->buildHeaders());

        try {
            $response = $this->getHttpClient()->sendRequest($request);

            $this->setLastStatusCode($response->getStatusCode());
        } catch (\RuntimeException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if (!in_array($response->getStatusCode(), [200, 404])) {
            throw $this->createErrorException($response);
        }

        return $response;
    }

    private function httpPost(Uri $url, array $payload = array())
    {
        $body = (!empty($payload) ? json_encode($payload) : null);
        $request = new Request('POST', $url, $this->buildHeaders(), $body);

        try {
            $response = $this->getHttpClient()->sendRequest($request);

            $this->setLastStatusCode($response->getStatusCode());
        } catch (\RuntimeException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if (!in_array($response->getStatusCode(), [200, 201, 204])) {
            throw $this->createErrorException($response);
        }

        return $response;
    }

    private function httpPatch(Uri $url, array $payload)
    {
        $request = new Request('PATCH', $url, $this->buildHeaders(), json_encode($payload));

        try {
            $response = $this->getHttpClient()->sendRequest($request);

            $this->setLastStatusCode($response->getStatusCode());
        } catch (\RuntimeException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() != 200) {
            throw $this->createErrorException($response);
        }

        return $response;
    }

    private function httpDelete(Uri $url)
    {
        $request = new Request('DELETE', $url, $this->buildHeaders());

        try {
            $response = $this->getHttpClient()->sendRequest($request);

            $this->setLastStatusCode($response->getStatusCode());
        } catch (\RuntimeException $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if (!in_array($response->getStatusCode(), [204])) {
            throw $this->createErrorException($response);
        }

        return $response;
    }

    // Response Handling

    /**
     * Called with a response from the API when the response code was unsuccessful. i.e. not 20X.
     *
     * @param ResponseInterface $response
     *
     * @return Exception\ResponseException
     */
    protected function createErrorException(ResponseInterface $response)
    {
        $body = json_decode($response->getBody(), true);

        $message = "HTTP:{$response->getStatusCode()} - ";
        $message .= (is_array($body)) ? print_r($body, true) : 'Unexpected response from server';

        return new Exception\ResponseException($message, $response->getStatusCode(), $response);
    }

    // Getters and setters

    /**
     * @param string $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return Exception\ResponseException|Response\Error|array|bool|string
     * @throws \Exception
     */
    public function getUserId()
    {
        if (is_null($this->userId)) {
            $response = $this->getTokenInfo($this->getToken());

            if ($response instanceof Response\ErrorInterface) {
                if ($response instanceof \Exception) {
                    throw $response;
                }

                return $response;
            }

            if (!is_array($response) || !isset($response['userId'])) {
                return false;
            }

            $this->setUserId($response['userId']);
        }

        return $this->userId;
    }

    /**
     * @return string
     */
    public function getAuthBaseUri()
    {
        return $this->authBaseUri;
    }

    /**
     * @return string
     */
    public function getApiBaseUri()
    {
        return $this->apiBaseUri;
    }

    /**
     * @return string
     */
    final public function getToken()
    {
        return $this->token;
    }

    /**
     * @param $token string
     * @return $this
     */
    final public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return HttpClientInterface
     */
    final protected function getHttpClient()
    {
        if (!$this->httpClient instanceof HttpClientInterface) {
            // @todo - For now create this using Guzzle v5.
            // This should be removed when the tight couple to Guzzle v5 is removed.

            $this->httpClient = new Guzzle5Client(new GuzzleHttpClient(), new GuzzleMessageFactory());
        }

        return $this->httpClient;
    }

    /**
     * @param HttpClientInterface $client
     */
    final protected function setHttpClient(HttpClientInterface $client)
    {
        $this->httpClient = $client;
    }
}
