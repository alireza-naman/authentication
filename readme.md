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

Developed by [Joel Vardy][joelvardy].

  [joelvardy]: https://joelvardy.com/