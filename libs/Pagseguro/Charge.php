<?php declare(strict_types=1);

namespace Libs\Pagseguro;

use Libs\Pagseguro\Exceptions\InvalidParameterType;

/**
 * Pagseguro Charge API.
 * 
 * @author Diego Gomes <dgs190plc@outlook.com>
 * @since 01/05/2022
 * @version 1.0.0
 */
class Charge extends API
{
    /**
     * Define order endpoints.
     * 
     * @param ... parent constructor
     */
    public function __construct(string $env, string $clientId, string $clientSecret, string $bearer)
    {
        parent::__construct($env, $clientId, $clientSecret, $bearer);

        $this->requestBody->payment_method = (object)[];

        $this->endpoints = (object)[
            'makeCharge' => ['POST', '/charges'],
            'cancelCharge' => ['POST', '/charges/{charge_id}/cancel']
        ];
    }

    /**
     * Set installments number.
     * 
     * @param int $installments
     * 
     * @return void
     */
    public function setInstallments(int $installments): void
    {
        $this->requestBody->payment_method->installments = $installments;
    }

    /**
     * Pay charge with credit card.
     * 
     * @return void
     */
    public function useCreditCard(): void
    {
        $this->requestBody->payment_method->type = "CREDIT_CARD";
    }

    /**
     * Pay charge with debit card.
     * 
     * @return void
     */
    public function useDebitCard(): void
    {
        $this->requestBody->payment_method->type = "DEBIT_CARD";
    }

    /**
     * Set credit/debit card data to payment.
     * 
     * @param string $store
     * @param string $number
     * @param int $expiresMonth
     * @param int $expiresYear
     * @param string $holder
     * 
     * @return void
     * 
     * @throws Libs\Pagseguro\Exceptions\InvalidParameterType
     */
    public function setCard(string $store, string $number, int $expiresMonth, int $expiresYear, int $cvv, string $holder): void
    {
        if (gettype($number) !== 'string' && gettype($number) !== 'integer') {
            throw new InvalidParameterType('card->number', gettype($number), 'string or int');
        }

        $this->requestBody->payment_method->capture = false;
        $this->requestBody->payment_method->soft_descriptor = $store;

        $this->requestBody->payment_method->card = (object)[
            'number' => preg_replace('/[^0-9]/', '', (string)$number),
            'expires_month' => (string)$expiresMonth,
            'expires_year' => (string)$expiresYear,
            'holder' => (object)[
                'name' => $holder
            ]
        ];
    }

    /**
     * Adds charge description and value.
     * 
     * @param string $description
     * @param int $value
     * 
     * @return void
     */
    public function add(string $description, int $value): void
    {
        $this->requestBody->description = $description;
        $this->requestBody->amount = (object)[
            'value' => $value,
            'currency' => 'BRL'
        ];
    }

    /**
     * Set charge to be created with recurrence.
     * 
     * @return void
     */
    public function setRecurrence(): void
    {
        $this->requestBody->recurring = (object)[
            'type' => 'INITIAL'
        ];
    }

    /**
     * Finish the charge.
     * 
     * @return object
     */
    public function finish(): object
    {
        $response = $this->client('makeCharge', [
            'body' => json_encode($this->requestBody),
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8'
            ]
        ]);

        return $response;
    }

    /**
     * Cancel charge.
     * 
     * @param string $chargeId
     * 
     * @return mixed
     */
    public function cancel(string $chargeId)
    {
        return $this->client('cancelCharge', ['replaces' => [
            'charge_id' => $chargeId
        ]]);
    }
}
