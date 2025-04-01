## PHP EasyApp

PHP Package for building Web applications as fast as possible.

This package enables you to create web services efficiently, allowing you to build robust solutions with minimal development time.

The idea is that a developer should spent minimum time on infrastructure of the application, reading a request and building the response. The developer should think only one the buisiness logic of the application. But should not care a lot about how to parse a request and how to build correct response format based on a request context.

**Example**

One of use cases.

Sometimes your application raises NotFoundException. Depending on a request you will want to return a JSON document with the error description, but in other case it would be a HTML page with the error. But yet on other case it will be a redirect to some other page. 

This application automates this. The format of the response is decided in the smart way depending on a context. 



### Installation
Using composer: [gelembjuk/php-easy-app](http://packagist.org/packages/gelembjuk/php-easy-app) ``` require: {"gelembjuk/php-easy-app": "*"} ```

```
composer require gelembjuk/php-easy-app
```

### Hello World!

```php
<?php

require '../src/vendor/autoload.php';

class HelloWorld extends \Gelembjuk\EasyApp\Controller {
	protected function get()
	{
		return "Hello World!";
	}
}

$action = new \Gelembjuk\EasyApp\Action();
$action->context->config->traceErrors = true;
$action->
	withDefaultController(HelloWorld::class)->
	action()->
	standardOutput();
```

### Usage

This is simplest "one file" example of an app usage. On a practice you will have smoe more files/folders. But it will be same standard structure for all our apps.

```php



```

### Author

Roman Gelembjuk (@gelembjuk)

