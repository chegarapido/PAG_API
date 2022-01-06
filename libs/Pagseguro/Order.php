<?php declare(strict_types=1);

namespace Libs\Pagseguro;

use Libs\Pagseguro\Exceptions\InvalidParameterType;

/**
 * Pagseguro Order API.
 * 
 * @author Diego Gomes <dgs190plc@outlook.com>
 * @since 01/04/2022
 * @version 1.0.0
 */
class Order extends API
{
    /**
     * Define order endpoints.
     * 
     * @param ... parent constructor
     */
    public function __construct(string $env, string $clientId, string $clientSecret, string $bearer)
    {
        parent::__construct($env, $clientId, $clientSecret, $bearer);

        $this->endpoints = (object)[
            'makeOrder' => ['POST', '/orders'],
            'getOrder' => ['GET', '/orders/{order_id}']
        ];
    }

    /**
     * Add customer to order.
     * 
     * @param string $name
     * @param string $email
     * @param string $document
     * @param string $phone
     * 
     * @return void
     */
    public function addCustomer(string $name, string $email, string $document, string $phone): void
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        $country = substr($phone, 0, 2);
        $ddd = substr($phone, 2, 4);
        $number = substr($phone, 4);

        $this->requestBody->customer = (object)[
            'name' => $name,
            'email' => $email,
            'tax_id' => preg_replace('/[^0-9]/', '', $document),
            'phones' => [
                (object)[
                    'country' => $country,
                    'area' => $ddd,
                    'number' => $number,
                    'type' => 'MOBILE'
                ],
            ]
        ];
    }

    /**
     * Add a item to order.
     * 
     * @param string $name
     * @param int $quantity
     * @param int $price
     * @param int|string $customId (optional, default null) If not passed will assume a rand number
     * 
     * @return void
     */
    public function addItem(string $name, int $quantity, int $price, $customId = null): void
    {
        if (!$customId) {
            $customId = time();
        } elseif (gettype($customId) !== 'string' && gettype($customId) !== 'integer') {
            throw new InvalidParameterType('custom_id', gettype($customId), 'string or int');
        }

        if (!isset($this->requestBody->items)) {
            $this->requestBody->items = array();
        }

        $this->requestBody->items[] = (object)[
            'reference_id' => (string)$customId,
            'name' => $name,
            'quantity' => $quantity,
            'unit_amount' => $price
        ];
    }

    /**
     * Pay order with PIX.
     * 
     * @return void
     */
    public function usePix(): void
    {
        $items = $this->requestBody->items;
        $totalPrice = 0;

        foreach ($items as $item) {
            $totalPrice += $item->unit_amount * $item->quantity;
        }

        $this->requestBody->qr_codes = [
            (object)[
                'amount' => (object)[
                    'value' => $totalPrice
                ]
            ]
        ];
    }

    /**
     * Pay order with credit card.
     * 
     * @param string $number
     * @param int $expiresMonth
     * @param int $expiresYear
     * @param string $holder
     * @param string $paymentDescription
     * @param string|int $customId (optional, default null) If not set, will assume a rand int 
     * @param int $installments (optional, default 1) Number of installments
     * 
     * @return void
     * 
     * @throws Libs\Pagseguro\Exceptions\InvalidParameterType
     */
    public function useCreditCard(string $number, int $expiresMonth, int $expiresYear, int $cvv, string $holder, string $paymentDescription, $customId = null, int $installments = 1): void
    {
        $items = $this->requestBody->items;
        $totalPrice = 0;

        foreach ($items as $item) {
            $totalPrice += $item->unit_amount * $item->quantity;
        }

        if (!$customId) {
            $customId = time();
        } elseif (gettype($customId) !== 'string' && gettype($customId) !== 'integer') {
            throw new InvalidParameterType('custom_id', gettype($customId), 'string or int');
        }

        if (gettype($number) !== 'string' && gettype($number) !== 'integer') {
            throw new InvalidParameterType('custom_id', gettype($number), 'string or int');
        }

        $this->requestBody->charges = [
            (object)[
                'reference_id' => (string)$customId,
                'description' => $paymentDescription,
                'amount' => (object)[
                    'value' => $totalPrice,
                    'currency' => 'BRL'
                ],
                'payment_method' => (object)[
                    'type' => 'CREDIT_CARD',
                    'installments' => $installments,
                    'capture' => true,
                    'card' => (object)[
                        'number' => preg_replace('/[^0-9]/', '', (string)$number),
                        'exp_month' => (string)$expiresMonth,
                        'exp_year' => (string)$expiresYear,
                        'security_code' => (string)$cvv,
                        'holder' => (object)[
                            'name' => $holder
                        ]
                    ],
                    'store' => false
                ],
            ]
        ];
    }

    /**
     * Finish the order.
     * 
     * @return object
     */
    public function finish(): object
    {
        $response = $this->client('makeOrder', [
            'body' => json_encode($this->requestBody),
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8'
            ]
        ]);

        return $response;
    }

    /**
     * Search and get data to a specified order by his ID.
     * 
     * @param string $orderId
     * 
     * @return mixed
     */
    public function get(string $orderId)
    {
        return $this->client('getOrder', ['replaces' => [
            'order_id' => $orderId
        ]]);
    }
}
