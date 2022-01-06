<?php

declare(strict_types=1);

namespace Libs\Crypt;

use \Exception;
use \InvalidArgumentException;

/**
 * Handle criptography mehtods.
 * 
 * @author Emmy Gomes <aou-emmy@outlook.com>
 * @since 12/23/2021
 * @version 1.0.0
 */
class Crypt
{
    /**
     * Stores keypair.
     * 
     * @property static object $keys
     */
    private static object $keys;

    /**
     * Stores your salt number. Default is 8.
     * 
     * @property static int $salt
     */
    public static int $salt = 8;

    /**
     * Stores replaces to be used in crypt/decrypt operations.
     * 
     * @property static object $replaces
     */
    private static object $replaces;

    /**
     * Stores characters array.
     * 
     * @property static array $characters
     */
    private static array $characters;

    /**
     * Stores input operation, that can be 'make' (encrypt) or 'restore' (decrypt).
     * 
     * @property static string $operation
     */
    private static string $operation;

    /**
     * Set class properties to his default values.
     * 
     * @return void
     */
    private function setDefaultProps(): void
    {
        self::$salt = 12;
        self::$keys = Key::getKeypair();
        self::$characters = array_merge(range('a', 'z'), range('A', 'Z'), range(0, 9), ['/', '=', '+']);

        self::$replaces = (object)[
            'original' => [' '],
            'new' => ['/']
        ];
    }

    /**
     * Class constructor.
     * 
     * @return void
     * 
     * @throws \Exception If operation type was not defined.
     */
    public function __construct()
    {
        if (!self::$operation || empty(self::$operation)) {
            throw new Exception('You must to define a operation before interact with other Libs\Crypt\Crypt methods.');
        }

        $this->setDefaultProps();
    }

    /**
     * Reset class properties.
     * 
     * @return void
     */
    public function __destruct()
    {
        self::$operation = '';
        
        $this->setDefaultProps();
    }

    /**
     * Set operation type.
     * 
     * @param string $type encrypt or decrypt
     * 
     * @return self
     * 
     * @throws \InvalidArgumentException If unknow operation type was passed.
     */
    private static function operation(string $type): self
    {
        if ($type !== 'encrypt' && $type !== 'decrypt') {
            throw new InvalidArgumentException("Unknow operation type {$type} passed to Libs\\Crypt\\Crypt class");
        }

        self::$operation = $type;

        return new self;
    }

    /**
     * Set operation to make.
     * 
     * @return self
     */
    public static function make(): self
    {
        return self::operation('encrypt');
    }

    /**
     * Set operation to undo.
     * 
     * @return self
     */
    public static function undo(): self
    {
        return self::operation('decrypt');
    }

    /**
     * Set a new salt value.
     * 
     * @param int $salt
     * 
     * @return self
     */
    public function salt(int $salt): self
    {
        self::$salt = $salt;
        return $this;
    }

    /**
     * Encrypt a string or int.
     * 
     * @param string|int $input
     * 
     * @return string $secret
     * 
     * @throws \InvalidArgumentException If $input is not from expected type.
     */
    public function encrypt($input): string
    {
        if (!in_array(gettype($input), ['string', 'integer'])) {
            throw new InvalidArgumentException('You can only do encryptation with string or int inputs');
        }

        $secret = '';
        $input = str_replace(self::$replaces->original, self::$replaces->new, (string)$input);
        $base64input = base64_encode($input);
        $encodedInputChars = str_split($base64input);

        foreach ($encodedInputChars as $char) {
            $position = array_search($char, self::$characters);
            $alternativePosition = abs(($position + self::$salt) - (count(self::$characters) - 1)) - 1;
            
            $newChar = self::$characters[$position + self::$salt] ?? self::$characters[$alternativePosition == -1 ? 0 : $alternativePosition];

            $secret .= $newChar;
        }

        $secret = self::$keys->public . "\${$secret}\$" . self::$keys->private;
        return $secret;
    }

    /**
     * Decrypt secrets.
     * 
     * @param string $secret
     * 
     * @return string $original
     * 
     * @throws \Exception If keypair not pass in validation.
     */
    public function decrypt(string $secret): string
    {
        $parts = explode('$', $secret);

        if ($parts[0] !== self::$keys->public || $parts[2] !== self::$keys->private) {
            throw new Exception('Public and/or private keys are invalid.');
        }

        $original = '';
        $secret = $parts[1];
        $secretChars = str_split($secret);

        foreach ($secretChars as $char) {
            $position = array_search($char, self::$characters);
            $alternativePosition = (count(self::$characters)) - (abs($position - self::$salt));
            
            $newChar = self::$characters[$position - self::$salt] ?? self::$characters[$alternativePosition];

            $original .= $newChar;
        }

        $original = base64_decode($original);
        $original = str_replace(self::$replaces->new, self::$replaces->original, $original);

        return $original;
    }

    /**
     * Does the selected operation from a string.
     * 
     * @param string $input
     * 
     * @return string
     */
    public function fromString(string $input): string
    {
        $operation = self::$operation;

        return $this->$operation($input);
    }

    /**
     * Does the selected operation from a array.
     * 
     * @param array $input
     * @param array $targetKeys (optional) specifies the keys that must to be encrypted
     * 
     * @return array $output
     */
    public function fromArray(array $input, array $targetKeys = []): array
    {
        $output = array();
        $operation = self::$operation;

        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $output[$key] = call_user_func_array([$this, 'fromArray'], [$value, $targetKeys]);
                continue;
            }

            if (count($targetKeys) > 0) {
                if (in_array($key, $targetKeys)) {
                    $output[$key] = $this->$operation($value);
                } else {
                    $output[$key] = $value;
                }

                continue;
            }
            
            $output[$key] = $this->$operation($value);
        }

        return $output;
    }

    /**
     * Does the selected operation from a object or a array of objects.
     * 
     * @param array|object $input
     * @param array $targetKeys (optional) specifies the keys that must to be encrypted
     * 
     * @return array|object $output
     */
    public function fromObject($input, array $targetKeys = [])
    {
        $input = json_decode(json_encode($input), true);
        
        $output = $this->fromArray($input, $targetKeys);
        $output = json_decode(json_encode($output));

        return $output;
    }

    /**
     * Verifies if a string is encrypted.
     * 
     * @param string $input
     * 
     * @return bool
     */
    public static function isEncrypted(string $input): bool
    {
        $keys = Key::getKeypair();
        $parts = explode('$', $input);

        return isset($parts[2]) && $parts[0] === $keys->public && $parts[2] === $keys->private;
    }

    /**
     * Decrypt input string if is encrypted, case not just return it.
     * 
     * @param string $input
     * 
     * @return string
     */
    public static function resolve(string $input): string
    {
        return self::isEncrypted($input) ? self::undo()->fromString($input) : $input;
    }

    /**
     * Encode lead id, with old encription method.
     * 
     * @param string $id
     * 
     * @return string
     */
    public static function encodeId(string $id): string
    {
        return str_replace('%', 'os76Com', rawurlencode(base64_encode($id)));
    }

    /**
     * Decode lead id, with old encription method.
     * 
     * @param string $id
     * 
     * @return int
     */
    public static function decodeId(string $id): int
    {
        return intval(base64_decode(rawurldecode(str_replace('os76Com', '%', $id))));
    }
}
