<?php declare(strict_types=1);

namespace Libs\Pagseguro\Exceptions;

use Libs\Http\Response;

/**
 * Invalid environment exception.
 * 
 * @author Diego Gomes <dgs190plc@outlook.com>
 * @since 01/04/2022
 * @version 1.0.0
 */
class InvalidEnvironment
{
    /**
     * @param string $passedEnv
     */
    public function __construct(string $passedEnv)
    {
        $availableEnvs = [
            'prod' => 'Production environment.',
            'homolog' => 'Homologation environment. Actions here will not have real monetary effects and you can use test data as cards, values, etc.'
        ];
        
        $this->response($passedEnv, $availableEnvs);
    }

    /**
     * Show exception as response to request.
     * 
     * @param string $passedEnv
     * @param array $availableEnvs
     * 
     * @return void
     */
    private function response(string $passedEnv, array $availableEnvs): void
    {
        $response = [
            'status_code' => 400,
            'status_text' => 'Bad Request',
            'message' => "Passed {$passedEnv} environment not exists or are not available. Please inform a correct environment",
            'available_environments' => $availableEnvs
        ];

        Response::json($response, 400);
        die();
    }
}
