<?php declare(strict_types=1);

namespace Libs\Pagseguro\Exceptions;

use Libs\Http\Response;
use Libs\Http\Request;

/**
 * Invalid pagseguro API endpoint exception.
 * 
 * @author Diego Gomes <dgs190plc@outlook.com>
 * @since 01/04/2022
 * @version 1.0.0
 */
class UnknowEndpoint
{
    /**
     * @param string $endpoint
     */
    public function __construct(string $endpoint)
    {   
        $this->response($endpoint);
    }

    /**
     * Show exception as response to request.
     * 
     * @param string $endpoint
     * 
     * @return void
     */
    private function response(string $endpoint): void
    {
        $response = [
            'status_code' => 500,
            'status_text' => 'Internal Server Error',
            'message' => "Pagseguro endpoint {$endpoint} does not exists, check for your request type available endpoints.",
            'backtrace' => Request::isFromServer() ? debug_backtrace() : 'not available'
        ];

        Response::json($response, 500);
        die();
    }
}
