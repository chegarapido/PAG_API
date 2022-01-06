<?php declare(strict_types=1);

/**
 * Make orders.
 * 
 * @author Diego Gomes <dgs190plc@outlook.com>
 * @since 01/05/2022
 * @version 1.0.0
 */

require_once __DIR__ . '/vendor/autoload.php';

use Libs\Http\Request;
use Libs\Http\Response;
use Libs\Http\Middlewares\AuthOnly;
use Libs\Pagseguro\Order;
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

if (isset($request->payment_method) && $request->payment_method !== 'pix' && $request->payment_method !== 'credit_card') {
    $validationResponse[] = [
        'message' => 'Wrong payment_method passed. Supported payment_methods in orders are pix and credit_card'
    ];
}

if (isset($request->payment_method) && $request->payment_method === 'credit_card') {
    if (!isset($request->credit_card)) {
        $validationResponse[] = [
            'message' => 'Missing credit_card param object with credit card data to pay order.'
        ];
    } else {
        $creditCardParams = ['number', 'cvv', 'holder', 'expires_month', 'expires_year', 'payment_description'];

        foreach ($creditCardParams as $param) {
            if (!isset($request->credit_card->$param)) {
                $validationResponse[] = [
                    'message' => "Missing credit_card->{$param} param in credit card data."
                ];
            }
        }
    }
}

if (!isset($request->items) || !is_array($request->items) || !count($request->items)) {
    $validationResponse[] = [
        'message' => 'Missing items to order.'
    ];
} else {
    $requiredItemFields = ['name', 'price'];

    foreach ($requiredItemFields as $field) {
        foreach ($request->items as $key => $item) {
            if (!isset($item->$field)) {
                $key = $key + 1;
                $validationResponse[] = [
                    'message' => "Missing {$field} param to number {$key} item."
                ];
            }
        }
    }
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
// $credentials = Crypt::undo()->fromString($GLOBALS['AUTHORIZATION']);
// $credentials = json_decode($credentials);
$credentials = $request->credentials;

$order = new Order($request->ambient, $credentials->client_id, $credentials->client_secret, $credentials->bearer);

// Add items
foreach ($request->items as $item) {
    $quantity = isset($item->quantity) ? intval($item->quantity) : 1;
    $customId = isset($item->custom_id) ? $item->custom_id : null;

    $order->addItem($item->name, $quantity, $item->price, $customId);
}

// Choose payment methods
if ($request->payment_method === 'pix') {
    $order->usePix();
}

if ($request->payment_method === 'credit_card') {
    $card = $request->credit_card;
    $installments = isset($card->installments) ? intval($card->installments) : 1;

    $order->useCreditCard(
        (string)$card->number,
        $card->expires_month,
        $card->expires_year,
        intval($card->cvv),
        $card->holder,
        $card->payment_description,
        isset($card->custom_id) ? (string)$card->custom_id : null
    );
}

// Add metadata if was
if (isset($request->metadata)) {
    $order->setMetadata($request->metadata);
}

// Add notifications if was
if (isset($request->callbacks)) {
    foreach ($request->callbacks as $callback) {
        $order->addNotification($callback);
    }
}

// Add custom id if was
if (isset($request->custom_id)) {
    $order->setCustomId($request->custom_id);
}

Response::json($order->finish());
