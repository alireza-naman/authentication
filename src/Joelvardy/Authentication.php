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
			'password_field' => 'password',
			'password_cost' => 16
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


	/**
	 * Generate salt
	 *
	 * @return	string
	 */
	protected function generate_salt() {

		// We are using blowfish, so this must be set at the beginning of the salt
		$salt = '$2a$'.$this->config->password_cost.'$';

		// Generate a random string based on time
		$salt .= substr(str_replace('+', '.', base64_encode(sha1(microtime(TRUE), TRUE))), 0, 22);

		return $salt.'$';

	}


	/**
	 * Generate hash
	 *
	 * @param	string [$password] The password for which the hash should be generated for
	 * @param	string [$salt] The salt should be the current password, if none is provided one will be generated
	 * @return	string
	 */
	protected function generate_hash($password, $salt = null) {

		// Generate salt
		if ( ! $salt) {
			$salt = $this->generate_salt();
		}

		// Hash the generated details with a salt to form a secure password hash
		return crypt($password, $salt);

	}


	/**
	 * Create user
	 *
	 * @param	string [$username] The username of the user to be created
	 * @param	string [$password] The users password
	 * @param	integer [$group_id] The ID of the group in which the user will belong
	 * @return	integer|boolean
	 */
	public function create_user($username, $password, $group_id) {

		// Ensure username is available
		if ( ! $this->username_available($username)) return false;

		// Generate hash
		$password = $this->generate_hash($password);

		// Add group permission - on error return false
		if ( ! $stmt = Database::instance()->prepare("insert into {$this->config->user_table}(`id`, `group_id`, `created`, `updated`, `{$this->config->username_field}`, `{$this->config->password_field}`) values (0, ?, ?, ?, ?, ?)")) return false;
		$stmt->bind_param('iiiss', $group_id, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $username, $password);
		if ($stmt->execute()) {
			$user_id = $stmt->insert_id;
			$stmt->close();
			return $user_id;
		} else {
			$stmt->close();
			return false;
		}

	}


	/**
	 * Read user account
	 *
	 * @return	array|boolean
	 */
	public function read_user($user_id) {

		// Fetch cached copy of user details and return
		if ($user_details = Cache::fetch('user_'.$user_id)) return $user_details;

		// Select permissions
		if ( ! $stmt = Database::instance()->prepare("select `id`, `group_id`, `created`, `updated`, `{$this->config->username_field}` from {$this->config->user_table} where `id` = ?")) return false;
		$stmt->bind_param('i', $user_id);
		$stmt->execute();
		$stmt->bind_result($id, $group_id, $created, $updated, $username);
		$stmt->fetch();
		$user_details = array(
			'id' => $id,
			'created' => $created,
			'updated' => $updated,
			$this->config->username_field => $username
		);
		$stmt->close();

		// Read group details
		$user_details['group'] = $this->read_groups($group_id);

		// Cache user details
		Cache::store('user_'.$user_id, $user_details);

		return $user_details;

	}


}