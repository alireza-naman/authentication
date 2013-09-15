# Authentication Library

An authentication library which provides permission based access control and requires minimal setup.

## Usage

### Configuration

The table below details the available configuration options:

| Option          | Description
| --------------- | -----
| secret_key      | Used to add entropy within user fingerprint.

Define these options as shown below:

```php
// Define authentication details
Joelvardy\Config::value('authentication', (object) array(
	'secret_key' => '|X^9VoS4CO7!J6Q@Gg7zE9@hl[q+ki'
));
```

### Caching

The library uses this [caching library][cache], although you don't have to use it, you can define a memcached server to use, or if you don't the library will simpily query the database when required.

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

Carefully developed by [Joel Vardy][joelvardy], however I can't take responsibilty for any damage caused by this library.

  [joelvardy]: https://joelvardy.com/
  [cache]: https://github.com/joelvardy/cache