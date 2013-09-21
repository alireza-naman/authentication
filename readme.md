# Authentication Library

An authentication library which provides permission based access control and requires minimal setup.

## Usage

### Configuration

The table below details the available configuration options:

| Option          | Description
| --------------- | -----
| secret_key      | Used to add entropy within user fingerprint.
| user_table      | *[optional]* Set a custom user table name, default: user
| username_field  | *[optional]* Set a custom username field name, default: username
| password_field  | *[optional]* Set a custom password field name, default: password
| password_cost   | *[optional]* Strength of the password hash, within the range 04 - 31 default: 16
| session_name   | *[optional]* The name of the user session, default: `jv_authentication`

You should define these options as shown below:

```php
// Define authentication details
Joelvardy\Config::value('authentication', (object) array(
	'secret_key' => '|X^9VoS4CO7!J6Q@Gg7zE9@hl[q+ki'
));
```

#### Database

This library also makes use of my [database connection library][database-library] for managing database connections. You will need to define your database details as shown below:

```php
// Define database details
Joelvardy\Config::value('database', (object) array(
    'host' => 'localhost',
    'username' => 'joelvardy',
    'password' => 'CHANGEME',
    'name' => 'projectx'
));
```

### Caching

The library uses this [caching library][cache], although you don't have to use it, you can define a memcached server to use, or if you don't the library will simply query the database when required.

### Tables

#### Permissions

The permissions table holds each possible permission, you must create the table with the SQL below:

```sql
CREATE TABLE `user_permission` (
	`id` int NOT NULL AUTO_INCREMENT,
	`key` varchar(128) NOT NULL,
	`title` varchar(128) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

#### Groups

Each user is assigned to a group, use the SQL below to create this groups table:

```sql
CREATE TABLE `user_group` (
	`id` int NOT NULL AUTO_INCREMENT,
	`title` varchar(56) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

There is a link table which assigns permissions to groups, the SQL below should be used to create this table:

```sql
CREATE TABLE `user_group_permission` (
	`id` int NOT NULL AUTO_INCREMENT,
	`group_id` int NOT NULL,
	`permission_id` int NOT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT fk_group_permission_group FOREIGN KEY (group_id) REFERENCES user_group(id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_group_permission_permission FOREIGN KEY (permission_id) REFERENCES user_permission(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

There are no methods to add rows to these tables, it must be done manually. If caching is running you should run the `flush_permissions()` method to clear the cache.

#### User

You must have a table which contains the following fields:

 * id (integer)
 * group_id (integer)
 * created (integer)
 * updated (integer)
 * username field (string)
 * password field (string)

You can use the configuration options to change the name of the table and the username and password fields. The table may have other fields (such as name and biography) which you can access through your own models, but these must allow null entries.

SQL to create a table which will work *out of the box* is shown below:

```sql
CREATE TABLE `user` (
	`id` int NOT NULL AUTO_INCREMENT,
	`group_id` int NOT NULL,
	`created` int NOT NULL,
	`updated` int NOT NULL,
	`username` varchar(128) NOT NULL,
	`password` varchar(128) NOT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT fk_user_group FOREIGN KEY (group_id) REFERENCES user_group(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

### Creating a User

Before you create a user you should check the username is available using the `username_available($username)` method.

The `create_user()` method will return false if the username has already been taken. The method takes three parameters; username, password and the user's group ID. Below is an example:

```php
$auth = new Joelvardy\Authentication();

$user_id = $auth->create_user($username, $password, MEMBER_GROUP_ID);
if ( ! $user_id) {
	// Some error has occurred
}
```

### Changing a Password

The example below shows how to change a password:

```php
$auth = new Joelvardy\Authentication();

if ($auth->change_password($user_id, $password)) {
	// Password changed
}
```

When you call this method the `updated` field in the user table will be updated.

### Changing the Group

If you want to change the group a user belongs to you can use the `change_group($user_id, $group_id)` method. When you call this the `updated` field in the user table will also be updated.

### Logging In

An example showing how to log a user in is show below:

```php
$auth = new Joelvardy\Authentication();

if ($auth->login($username, $password)) {
	// Successfully logged in
}
```

*You should probably implement some brute force protection, for example, if a user enters the password wrong four times, they are locked out for 10 minutes.*

### Are You Logged In?

To check a user is logged in just run `$auth->logged_in()` it will return either true or false.

You can also read the logged in users ID, like so: `$auth->user_id()`

### Checking User Permissions

You can easily check whether a user has a permission listed in the `user_permission` table, the example below shows this:

```php
$auth = new Joelvardy\Authentication();

if ($auth->permission('moderate-comments')) {
	// This user can moderate comments
}
```

### Logging Out

To log the current user out just run: `$auth->logout()`

Carefully developed by [Joel Vardy][joelvardy], however I can't take responsibility for any damage caused by this library.

  [joelvardy]: https://joelvardy.com/
  [cache]: https://github.com/joelvardy/cache
  [database-library]: https://github.com/joelvardy/database