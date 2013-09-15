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
		$this->config = (object) array_merge(array(
			'user_table' => 'user',
			'username_field' => 'username',
			'password_field' => 'password'
		), (array) clone Config::value('authentication'));

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
		Cache::delete('user_groups');

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
			$permissions[] = array(
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


	/**
	 * Read user groups
	 *
	 * @param	integer [$return_group_id] A group ID to return
	 * @return	array|boolean
	 */
	public function read_groups($return_group_id = null) {

		// Fetch cached copy of groups and return
		if ($groups = Cache::fetch('user_groups')) {
			if ($return_group_id) {
				return (isset($groups[$return_group_id]) ? $groups[$return_group_id] : false);
			}
			return $groups;
		}

		// Define an empty array for the groups
		$groups = array();

		// Select groups
		if ( ! $stmt = Database::instance()->prepare('select user_group.id, user_group.title, user_permission.id, user_permission.key, user_permission.title from `user_group`, `user_group_permission`, `user_permission` where user_group.id = user_group_permission.group_id and user_permission.id = user_group_permission.permission_id order by user_group.id asc')) return false;
		$stmt->execute();
		$stmt->bind_result($group_id, $group_title, $permission_id, $permission_key, $permission_title);
		while($stmt->fetch()) {
			// If this is the first item define the groups variable and title
			if ( ! isset($groups[$group_id])) {
				$groups[$group_id] = array(
					'title' => $group_title,
					'permissions' => array(
						$permission_id => array(
							'key' => $permission_key,
							'title' => $permission_title
						)
					)
				);
			}
			// Add the group permissions
			$groups[$group_id]['permissions'][$permission_id] = array(
				'key' => $permission_key,
				'title' => $permission_title
			);
		}
		$stmt->close();

		// Cache groups
		Cache::store('user_groups', $groups);

		if ($return_group_id) {
			return (isset($groups[$return_group_id]) ? $groups[$return_group_id] : false);
		}
		return $groups;

	}


	/**
	 * Is the username available
	 *
	 * @param	string [$username] The username to check against
	 * @return	boolean
	 */
	public function username_available($username) {

		if ( ! $stmt = Database::instance()->prepare("select `id` from {$this->config->user_table} where {$this->config->username_field} = ?")) return false;
		$stmt->bind_param('s', $username);
		$stmt->execute();
		$stmt->bind_result($id);
		// There is already a user with this username
		if ($stmt->fetch()) {
			$stmt->close();
			return false;
		// No user was found
		} else {
			$stmt->close();
			return true;
		}

	}


}