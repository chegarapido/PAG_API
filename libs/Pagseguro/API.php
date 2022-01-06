<?php declare(strict_types=1);

namespace Libs\Pagseguro;

use Libs\Http\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;
use Libs\Pagseguro\Exceptions\InvalidEnvironment;
use Libs\Pagseguro\Exceptions\UnknowEndpoint;
use Libs\Pagseguro\Exceptions\APIException;
use Libs\Pagseguro\Exceptions\InvalidParameterType;

/**
 * Pagseguro superclass API.
 * 
 * @author Diego Gomes <dgs190plc@outlook.com>
 * @since 01/04/2022
 * @version 1.0.0
 */
class API
{
    /**
     * API entrypoints.
     * 
     * @property object $entrypoints
     */
    private object $entrypoints;

    /**
     * Stores API endpoints. Can be modified by child classes.
     * 
     * @property object $endpoints
     */
    protected object $endpoints;

    /**
     * Stores API environment.
     * 
     * @property string $env
     */
    private string $env;

    /**
     * Stores API credentials.
     * 
     * @property object $credentials
     */
    private object $credentials;

    /**
     * Stores guzzle client.
     * 
     * @property GuzzleHttp\Client $client
     */
    protected Client $client;

    /**
     * Pagseguro request body. Acessible by child classes.
     * 
     * @property object $requestBody
     */
    protected object $requestBody;

    /**
     * @param string $env Environment to use.
     * 
     * @return void
     * 
     * @throws Libs\Pagseguro\Exceptions\InvalidEnvironment
     */
    public function __construct(string $env, string $clientId, string $clientSecret, string $bearer)
    {
        if ($env !== 'homolog' && $env !== 'prod') {
            throw new InvalidEnvironment($env);
        }

        $this->env = $env;

        $this->entrypoints = (object)[
            'homolog' => 'https://sandbox.api.pagseguro.com',
            'prod' => 'https://api.pagseguro.com'
        ];

        $this->client = new Client();
        
        $this->credentials = (object)[
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'bearerToken' => $bearer
        ];

        $this->requestBody = (object)[];
    }

    /**
     * Mount URL to endpoint.
     * 
     * @param string $endpoint
     * 
     * @return string
     * 
     * @throws Libs\Pagseguro\Exceptions\UnknowEndpoint
     */
    protected function url(string $endpoint): string
    {
        if (!isset($this->endpoints->$endpoint)) {
            throw new UnknowEndpoint($endpoint);
        }

        $env = $this->env;
        $url = $this->entrypoints->$env . '/';
        $endpoint = $this->endpoints->$endpoint;

        if (substr($endpoint[1], 0, 1) == '/') {
            $endpoint[1] = substr($endpoint[1], 1);
        }

        return $url . $endpoint[1];
    }

    /**
     * Get guzzle method to endpoint.
     * 
     * @param string $endpoint
     * 
     * @return string
     * 
     * @throws Libs\Pagseguro\Exceptions\UnknowEndpoint
     */
    protected function method(string $endpoint): string
    {
        if (!isset($this->endpoints->$endpoint)) {
            throw new UnknowEndpoint($endpoint);
        }

        $endpoint = $this->endpoints->$endpoint;

        return strtolower($endpoint[0]);
    }

    /**
     * Set a custom ID to payment.
     * 
     * @param int|string $id
     * 
     * @return void
     * 
     * @throws Libs\Pagseguro\Exceptions\InvalidParameterType
     */
    public function setCustomId($id): void
    {
        if (gettype($id) != 'integer' && gettype($id) !== 'string') {
            throw new InvalidParameterType('custom_id', gettype($id), 'string or int');
        }

        $this->requestBody->reference_id = (string)$id;
    }

    /**
     * Set metadata to payment.
     * 
     * @param array|object $data
     * 
     * @return void
     */
    public function setMetadata($data): void
    {
        if (gettype($data) != 'object' && gettype($data) !== 'array') {
            throw new InvalidParameterType('metadata', gettype($data), 'array or object');
        }

        $this->requestBody->metadata = $data;
    }

    /**
     * Adds a notification webhook to payment.
     * 
     * @param string $url
     * 
     * @return void
     */
    public function addNotification(string $url): void
    {
        if (!isset($this->requestBody->notification_urls)) {
            $this->requestBody->notification_urls = array();
        }

        $this->requestBody->notification_urls[] = $url;
    }

    /**
     * Makes a request using guzzle client.
     * 
     * @param string $endpoint
     * @param array $options
     * 
     * @return mixed
     * 
     * @throws Libs\Pagseguro\Exceptions\APIException
     */
    protected function client(string $endpoint, array $options)
    {
        $url = $this->url($endpoint);
        $method = $this->method($endpoint);

        $error = null;

        if (!isset($options['headers']['Authorization'])) {
            $options['headers'] = $options['headers'] ?? [];
            $options['headers']['Authorization'] = 'Bearer ' . $this->credentials->bearerToken;
        }

        $options['cert'] = realpath(__DIR__ . '/../../certificates/ChegaRapido_SNDBX.pem');
        $options['ssl_key'] = realpath(__DIR__ . '/../../certificates/ChegaRapido_SNDBX.key');

        if (isset($options['replaces'])) {
            $url = str_replace(array_keys($options['replaces']), array_values($options['replaces']), $url);
        }

        try {
            $response = $this->client->$method($url, $options);
            $response = $response->getBody()->getContents();

            $json = json_decode($response);

            return $json;
        } catch (ServerException $e) {
			$error = $e;
			$errorType = "5xx";
		} catch (ClientException $e) {
			$error = $e;
			$errorType = "4xx";
		}

        throw new APIException($error, $errorType, $endpoint, $options);
    }
}
