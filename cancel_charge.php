<?php declare(strict_types=1);

/**
 * Cancel charges.
 * 
 * @author Diego Gomes <dgs190plc@outlook.com>
 * @since 01/04/2022
 * @version 1.0.0
 */

require_once __DIR__ . '/vendor/autoload.php';

use Libs\Http\Request;
use Libs\Http\Response;
use Libs\Http\Middlewares\AuthOnly;
use Libs\Pagseguro\Charge;
use Libs\Crypt\Crypt;

// die(Crypt::make()->fromString(json_encode(['client_id' => 'f01090f6-5d13-11ec-bf63-0242ac130002', 'client_secret' => 'f0109358-5d13-11ec-bf63-0242ac130002', 'bearer' => '05ee0182-c9d6-4f29-a858-13832653e22344c678f445e6bcb6ae41ad45b5b541bfefa6-1095-4b62-9474-581b7851673b'])));

// Middlewares to page
AuthOnly::guard();

// Getting query params in object format
$request = json_decode(json_encode($_GET));

// Validations
$validationResponse = array();

if (!isset($request->ambient)) {
    $validationResponse[] = [
        'message' => 'No ambient specified.'
    ];
}

if (!isset($request->payment_method)) {
    $validationResponse[] = [
        'message' => 'No payment method specified.'
    ];
}

if (!isset($request->order_id)) {
    $validationResponse[] = [
        'message' => 'No charge_id specified.'
    ];
}
if (count($validationResponse)) {
    Response::json([
        'status_code' => 400,
        'status_text' => 'Bad Request',
        'message' => 'You have missing params or wrong params errors in your request body',
        'errors' => $validationResponse
    ], 400);
    die();
}

// Start pagseguro api
$credentials = Crypt::undo()->fromString($GLOBALS['AUTHORIZATION']);
$credentials = json_decode($credentials);

$charge = new Charge($request->ambient, $credentials->client_id, $credentials->client_secret, $credentials->bearer);

Response::json($charge->cancel($request->charge_id));
