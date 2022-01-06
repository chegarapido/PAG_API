<?php declare(strict_types=1);

/**
 * Make charges.
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

// Getting body
$request = Request::parseJsonBody();

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

if (isset($request->payment_method) && $request->payment_method !== 'credit_card' && $request->payment_method !== 'debit_card') {
    $validationResponse[] = [
        'message' => 'Wrong payment_method passed. Supported payment_methods in charges are credit_card and debit_card'
    ];
}

if (isset($request->payment_method)) {
    if (!isset($request->card)) {
        $validationResponse[] = [
            'message' => 'Missing card param object with credit/debit card data to pay charge.'
        ];
    } else {
        $creditCardParams = ['number', 'cvv', 'holder', 'expires_month', 'expires_year', 'store_manufaturer'];

        foreach ($creditCardParams as $param) {
            if (!isset($request->card->$param)) {
                $validationResponse[] = [
                    'message' => "Missing credit_card->{$param} param in credit card data."
                ];
            }
        }
    }
}

if (!isset($request->value)) {
    $validationResponse[] = [
        'message' => 'Missing charge value.'
    ];
}

if (!isset($request->description)) {
    $validationResponse[] = [
        'message' => 'Missing charge description.'
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

// Add charge data
$charge->add($request->description, $request->value);

// Choose payment methods
if ($request->payment_method === 'credit_card') {
    $charge->useCreditCard();
} else {
    $charge->useDebitCard();
}

// Add card data
$card = $request->card;
$installments = isset($card->installments) ? intval($card->installments) : 1;

$charge->setCard(
    $card->store_manufaturer,
    (string)$card->number,
    intval($card->expires_month),
    intval($card->expires_year),
    intval($card->cvv),
    $card->holder,
);

// Add metadata if was
if (isset($request->metadata)) {
    $charge->setMetadata($request->metadata);
}

// Add notifications if was
if (isset($request->callbacks)) {
    foreach ($request->callbacks as $callback) {
        $charge->addNotification($callback);
    }
}

// Add custom id if was
if (isset($request->custom_id)) {
    $charge->setCustomId($request->custom_id);
}

// Add recurrence if requested
if (isset($request->use_recurrence) && $request->use_recurrence === true) {
    $charge->setRecurrence();
}

Response::json($charge->finish());
