<?php declare(strict_types=1);

/**
 * Return encrypted JSON request body.
 * 
 * @author Diego Gomes <dgs190plc@outlook.com>
 * @since 01/04/2022
 * @version 1.0.0
 */

require_once __DIR__ . '/vendor/autoload.php';

use Libs\Http\Request;
use Libs\Http\Response;
use Libs\Crypt\Crypt;

// Getting body
$request = Request::parseJsonBody();

$string = Crypt::make()->fromString(json_encode($request));

$response = (object)[
    'string' => $string
];

Response::json($response);
