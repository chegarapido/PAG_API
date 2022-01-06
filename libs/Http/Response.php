<?php declare(strict_types=1);

namespace Libs\Http;

/**
 * Library response interface.
 * 
 * @author Diego Gomes <dgs190plc@outlook.com>
 * @since 01/04/2022
 * @version 1.0.0
 */
class Response
{
    /**
     * Set response status code.
     * 
     * @param int $statusCode
     * 
     * @return self
     */
    public static function status(int $statusCode): self
    {
        http_response_code($statusCode);
        return new self();
    }

    /**
     * Set response header.
     * 
     * @param string $header
     * @param mixed $value
     * 
     * @return self
     */
    public static function header(string $header, $value): self
    {
        header("{$header}: {$value}");
        return new self();
    }

    /**
     * Set multiple headers based on array input.
     * 
     * @param array $headers
     * 
     * @return self
     */
    public static function headers(array $headers): self
    {
        foreach ($headers as $header => $value) {
            self::header($header, $value);
        }

        return new self();
    }

    /**
     * Responds request with json.
     * 
     * @param array|object $json
     * @param int $statusCode
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException
     */
    public static function json($json, int $statusCode = 200): void
    {
        if (gettype($json) !== 'object' && gettype($json) !== 'array') {
            throw new InvalidArgumentException('Invalid type to json output. Inform a array or object variable.');
        }

        self::status($statusCode);
        self::header('Content-Type', 'application/json; charset=utf-8');
        echo json_encode($json);
    }
}