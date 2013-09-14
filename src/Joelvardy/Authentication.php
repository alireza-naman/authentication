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
	 * Initialise the library
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
		$fingerprint = md5($this->config->secret_key);
		$fingerprint .= $_SERVER['HTTP_USER_AGENT'];
		$fingerprint .= $this->ip();

		return sha1($fingerprint);

	}


	/**
	 * Flush permissions cache
	 *
	 * @return	void
	 */
	public function flush_permissions() {

		Cache::delete('user_permissions');

	}


	/**
	 * Read permissions
	 *
	 * @return	array|boolean
	 */
	public function read_permissions() {

		// Fetch cached copy of permissions and return
		if ($permissions = Cache::fetch('user_permissions')) return $permissions;

		// Define an empty array for the permissions
		$permissions = array();

		// Select permissions
		if ( ! $stmt = Database::instance()->prepare('select `id`, `key`, `title` from `user_permission` order by `id` asc')) return false;
		$stmt->execute();
		$stmt->bind_result($id, $key, $title);
		while($stmt->fetch()) {
			$permissions[] = (object) array(
				'id' => $id,
				'key' => $key,
				'title' => $title
			);
		}
		$stmt->close();

		// Cache permissions
		Cache::store('user_permissions', $permissions);

		return $permissions;

	}


}