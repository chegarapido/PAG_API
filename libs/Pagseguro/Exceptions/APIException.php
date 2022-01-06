<?php declare(strict_types=1);

namespace Libs\Pagseguro\Exceptions;

use GuzzleHttp\Psr7;
use Libs\Http\Response;
use Libs\Http\Request;

/**
 * Pagseguro api errors exception.
 * 
 * @author Diego Gomes <dgs190plc@outlook.com>
 * @since 01/04/2022
 * @version 1.0.0
 */
class APIException
{
    /**
     * @param GuzzleHttp\Exception\ServerException|GuzzleHttp\Exception\ClientException $error
     * @param string $errorType
     * @param string $endpoint
     * @param array $options
     */
    public function __construct($error, string $errorType, string $endpoint, array $options)
    {
        $apiResponse = Psr7\Message::toString($error->getResponse());
        $apiRequest = Psr7\Message::toString($error->getRequest());

        $this->response($apiResponse, $apiRequest, $endpoint, $options);
    }

    /**
     * Show exception as response to request.
     * 
     * @param string $apiResponse
     * @param string $apiRequest
     * @param string $endpoint
     * @param array $options
     * 
     * @return void
     */
    private function response(string $apiResponse, string $apiRequest, string $endpoint, array $options): void
    {
        $response = [
            'status_code' => 500,
            'status_text' => 'Internal Server Error',
            'message' => "An error has occurred when trying to fetch PagSeguro API.",
            'pagseguro_response' => $apiResponse
        ];

        if (Request::isFromServer()) {
            $response['pagseguro_request'] = $apiRequest;
            $response['guzzle_options'] = $options;
        }

        Response::json($response, 500);
        die();
    }
}
