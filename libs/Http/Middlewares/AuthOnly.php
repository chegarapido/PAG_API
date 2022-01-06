<?php declare(strict_types=1);

namespace Libs\Http\Middlewares;

use Libs\Http\Exceptions\InvalidRequestException;

/**
 * Middleware that allows only authenticated requests in page.
 * 
 * @author Diego Gomes <dgs190plc@outlook.com>
 * @since 01/04/2022
 * @version 1.0.0
 */
class AuthOnly
{
    public static function guard(): void
    {
        // if (!isset($_SERVER['HTTP_AUTHENTICATION']) || $_SERVER['HTTP_AUTHENTICATION'] !== 'Basic daysdvabd656czxtafs4334ybcas6q5w5rxacjhxabcasd54588uchxazcva456345') {
        //     throw new InvalidRequestException('unauthenticated');
        // }

        // if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
        //     throw new InvalidRequestException('unauthenticated');
        // } else {
        //     $GLOBALS['AUTHORIZATION'] = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
        // }
    }
}
