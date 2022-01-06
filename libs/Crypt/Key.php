<?php

declare(strict_types=1);

namespace Libs\Crypt;

/**
 * Handle all public/private keys operations.
 * 
 * @author Emmy Gomes <aou-emmy@outlook.com>
 * @since 12/23/2021
 * @version 1.0.0
 */
class Key
{
	/**
	 * Stores key hash secret.
	 * 
	 * @property static string $keysHash
	 */
	private static string $keyHash;

	/**
	 * Stores your private key.
	 * 
	 * @property static string $privateKey
	 */
	private static string $privateKey;

	/**
	 * Token to be able to obtain public key from remote.
	 * 
	 * @property static string $remoteToken
	 */
	private static string $remoteToken;

	/**
	 * Remote URL to get public key.
	 * 
	 * @property static string $remoteUrl
	 */
	private static string $remoteUrl;

	/**
	 * Class constructor.
	 * 
	 * @return void
	 */
	private static function init(): void
	{
		// Here you put your key hashing string, and your private key. Always change this when including this library
		// Recommended to use a 64 characters keys, and 32 characters hash string.
		self::$keyHash = 'dasd567ardasdad7654bdb6751623e12vgaa5$$';
		self::$privateKey = 'uicz67r6c7cbHCGFSA67567^%^&%Xhayg$%gbcahnsuhgdga5$%$%$%$%dagsdga';

		// Remote host configurations
		self::$remoteUrl = 'https://';
		self::$remoteToken = '';
	}

	/**
	 * Get the public key from remote.
	 * 
	 * @return string
	 */
	private static function getPublicKey(): string
	{
		return 'rd4esad56d5sadgashdvgasfd876atcasfc65ascabncackmzxncbvfbiojigdas';
	}

	/**
	 * Get keypair data.
	 * 
	 * @return object
	 */
	public static function getKeypair(): object
	{
		self::init();

		$publicKey = md5(self::getPublicKey() . self::$keyHash);
		$privateKey = md5(self::$privateKey . self::$keyHash);

		return (object)[
			'public' => base64_encode($publicKey . $privateKey),
			'private' => base64_encode($privateKey . $publicKey)
		];
	}
}
