# Authentication Library

An authentication library which features access control lists and requires minimal setup.

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

The library uses this [caching library][cache], you can define a memcached server to use, however if you don't the library will query the database when required.

### Tables

#### Permissions

The permissions table holds each possible permission, create the table with the SQL below:

```sql
CREATE TABLE `user_permission` (
	`id` int NOT NULL AUTO_INCREMENT,
	`key` varchar(128) NOT NULL,
	`title` varchar(128) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

You must manually add rows to the table, if caching is running you can clear the related keys with the `flush_permissions()` method.

Carefully developed by [Joel Vardy][joelvardy], however I can't take responsibilty for any damage caused by this library.

  [joelvardy]: https://joelvardy.com/
  [cache]: https://github.com/joelvardy/cache