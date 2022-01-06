<?php declare(strict_types=1);

namespace Libs\Http\Exceptions;

use Libs\Http\Response;

/**
 * Invalid request exception.
 * 
 * @author Diego Gomes <dgs190plc@outlook.com>
 * @since 01/04/2022
 * @version 1.0.0
 */
class InvalidRequestException
{
    /**
     * @param string $errorType
     */
    public function __construct(string $errorType)
    {
        if (!method_exists($this, $errorType)) {
            $this->defaultResponse();
        }

        $this->$errorType();
    }

    /**
     * Show response to invalid body exceptions.
     * 
     * @return void
     */
    private function body_type(): void
    {
        $response = [
            'status_code' => 400,
            'status_text' => 'Bad Request',
            'message' => 'Wrong body type passed, the expected is a valid JSON body.'
        ];

        Response::json($response, 400);
        die();
    }

    /**
     * Show response to not authenticathed request attempts.
     * 
     * @return void
     */
    private function unauthenticated(): void
    {
        $response = [
            'status_code' => 401,
            'status_text' => 'Unauthenticated',
            'message' => 'You trying to access a protected route without credentials or with invalid credentials.'
        ];

        Response::json($response, 401);
        die();
    }
}
