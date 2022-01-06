<?php declare(strict_types=1);

namespace Libs\Pagseguro\Exceptions;

use Libs\Http\Response;

/**
 * Wrong param types exception.
 * 
 * @author Diego Gomes <dgs190plc@outlook.com>
 * @since 01/04/2022
 * @version 1.0.0
 */
class InvalidParameterType
{
    /**
     * @param string $paramName
     * @param string $passedType
     * @param string $expectedType
     */
    public function __construct(string $paramName, string $passedType, string $expectedType)
    {        
        $this->response($paramName, $passedType, $expectedType);
    }

    /**
     * Show exception as response to request.
     * 
     * @param string $paramName
     * @param string $passedType
     * @param string $expectedType
     * 
     * @return void
     */
    private function response(string $paramName, string $passedType, string $expectedType): void
    {
        $response = [
            'status_code' => 400,
            'status_text' => 'Bad Request',
            'message' => "Wrong type to param {$paramName}. You've passed {$passedType} when the expected types is {$expectedType}",
            'passed_type' => $passedType,
            'expected_type' => $expectedType,
        ];

        Response::json($response, 400);
        die();
    }
}
