<?php

namespace Joelvardy;

/**
 * Authentication library
 *
 * @link	https://github.com/joelvardy/authentication
 * @author	Joel Vardy <info@joelvardy.com>
 */
class Authentication {


	protected $config;


	/**
	 * Return current users IP address
	 *
	 * @return	void
	 */
	public function __construct() {

		// Set configuration
		$this->config = Config::value('authentication');

		// Ensure a secret key has been set
		if ( ! isset($this->config->secret_key)) throw new \Exception('You must define a secret key.');

	}


	/**
	 * Return current users IP address
	 *
	 * @return	string
	 */
	public function ip() {

		// NOTE: This may return the IP address of a proxy server

		return (string) $_SERVER['REMOTE_ADDR'];

	}


	/**
	 * Return user fingerprint
	 *
	 * @return	string
	 */
	protected function fingerprint() {

		// Initialise the fingerprint with a static salt
		$fingerprint = md5($config->secret_key);
		$fingerprint .= $_SERVER['HTTP_USER_AGENT'];
		$fingerprint .= $this->ip();

		return sha1($fingerprint);

	}


}