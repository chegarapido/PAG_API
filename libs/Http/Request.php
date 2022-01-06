<?php declare(strict_types=1);

namespace Libs\Http;

use Libs\Http\Exceptions\InvalidRequestException;

/**
 * Library request interface.
 * 
 * @author Diego Gomes <dgs190plc@outlook.com>
 * @since 01/04/2022
 * @version 1.0.0
 */
class Request
{
    /**
     * Verifies if request has come from current domain.
     * 
     * Based on https://stackoverflow.com/a/34169811
     * 
     * @return bool
     */
    public static function isFromServer(): bool
    {
        $server = gethostbyname(gethostname());
        $origin = $_SERVER['REMOTE_ADDR'];

        return parse_url($server, PHP_URL_HOST) === parse_url($origin, PHP_URL_HOST);
    }

    /**
     * Parse JSON data from body.
     * 
     * @return object
     * 
     * @throws Libs\Http\Exceptions\InvalidRequestException
     */
    public static function parseJsonBody(): object
    {
        $body = file_get_contents('php://input');
        $json = json_decode($body);

        if (!$json) {
            throw new InvalidRequestException('body_type');
        }

        return $json;
    }
}
