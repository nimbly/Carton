# Carton

[![Latest Stable Version](https://img.shields.io/packagist/v/nimbly/carton.svg?style=flat-square)](https://packagist.org/packages/nimbly/carton)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/nimbly/carton/php.yml?style=flat-square)](https://github.com/nimbly/Carton/actions/workflows/php.yml)
[![Codecov branch](https://img.shields.io/codecov/c/github/nimbly/carton/master?style=flat-square)](https://app.codecov.io/github/nimbly/Carton)
[![License](https://img.shields.io/github/license/nimbly/carton.svg?style=flat-square)](https://packagist.org/packages/nimbly/carton)


A simple PSR-11 container implementation.

## Features

* PSR-11 compliant
* Singleton and factory builders
* Service providers
* Nested container support
* Reflection based autowiring

## Install
```bash
composer require nimbly/carton
```

## Getting the container

### Instantiate container

You can create a new instance of a Container.

```php
$container = new Container;
```

### Singleton instance

Or use the container singleton `getInstance` method.

```php
$container = Container::getInstance();
```

## Basic usage

### Set an instance

The most basic usage is to just assign an instance to the container itself.

```php
$container->set(SomeInterface::class, new SomeClass);
```

Of course, you don't *need* to assign objects - it can be anything you like.

```php
$container->set("timezone", "UTC");
```

### Retrieving a value

Grab a value from the container by its key.

```php
$someClass = $container->get(SomeClass::class);
```

NOTE: Retrieving a value that does not exist will throw a `NotFoundException`.

### Checking for instance

You can check for the existance of items registered in the container.

```php
if( $container->has(SomeClass::class) ){
	echo "Container has SomeClass::class.";
}
```

## Advanced usage

### Singleton builder

The singleton builder will ensure only a single instance is ever returned when it is retrieved from the container.

It also has the added benefit over the `set` method by lazily instantiating the class. I.e. it will only be created when it is actually needed.

```php
$container->singleton(
	SomeClass::class,
	function(Container $container): void {
		return new SomeClass(
			$container->get("SomeDependency")
		);
	}
);
```

### Factory builder

The factory builder will create new instances each time it is retrieved from the container.

```php
$container->factory(
	SomeClass::class,
	function(Container $container): void {
		return new SomeClass(
			$container->get("SomeDependency")
		);
	}
);
```

### Autowiring

You can have instances made for you automatically using the `make` method - which will attempt to pull dependencies in from the container itself or recursively attempt to `make` them if not found.

```php
class Foo
{
	public function __construct(
        protected DateTime $date)
	{
	}
}

class Bar
{
	public function __construct(
        protected Foo $foo)
	{
	}
}

$bar = $container->make(Bar::class);
```

### Dependecy injection on instance methods

Calling an instance method couldn't be easier - Carton will attempt to autoresolve dependencies (autowire) for you when making a call to an instance method.

```php
class BooksController
{
	public function get(ServerRequestInterface $request, string $isbn): Response
	{
		return new Response(
			Books::find($isbn)
		);
	}
}

$container->set(ServerRequestInterface::class, $serverRequest);
$response = $container->call([BooksController::class, "get"], ["isbn" => "123123"]);
```

### Adding additional containers

You can extend Carton with additional PSR-11 compliant container instances by calling the `addContainer` method. When Carton attempts to resolve an item, it will
always attempt to resolve locally first, and if not found, will loop through any additional containers you have provided.

For example, if you had a configuration manager that implemented `ContainerInterface` (PSR-11),
you could add it to Carton.

```php
$container->addContainer(
	new Config([
		new FileLoader(__DIR__ . "/config")
	])
);

$container->get("database.connections");
```
Now you can retrieve your configuration data through the container instance.

### Service providers

Service providers allow you to organize your application dependencies in a set of classes.

Create service classes that implement `ServiceProviderInterface`.

```php
class MyServiceProvider implements ServiceProviderInterface
{
	public function register(Container $container): void
	{
		$container->singleton(
			MyService::class,
			function(Container $container): void {
				return new MyService(
					$container->get(SomeDependency::class)
				);
			}
		);
	}
}
```

Then register your service providers with the container.

```php
$container->register(new MyServiceProvider);
```

You can also register multiple services at once and register services by their class name.


```php
// Register group of services at once.
$container->register([
	new MyServiceProvider,
	new MyOtherServiceProvider
]);

// Register services by class name.
$container->register(MyServiceProvider::class);

// Register group of services by class name.
$container->register([
	MyServiceProvider::class,
	MyOtherServiceProvider::class
]);
```