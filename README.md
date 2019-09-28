# Carton
A simple PSR-11 container implementation.

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

### Singleton container

Or use the container singleton ```getInstance``` method.

```php
$container = Container::getInstance();
```

## Basic usage

### Set an instance

The most basic usage is to just assign an instance to the container itself.

```php
$container->set(SomeClass::class, new SomeClass);
```

Of course, you don't need to assign objects - it can be anyhing you like.

```php
$container->set('timezone', 'UTC');
```

### Retriving a value

Grab a value from the container by its key.

```php
$someClass = $container->get(SomeClass::class);
```

Retrieving a value that does not exist will throw a ```NotFoundException```.

### Checking for instance

You can check for the existance of items registered in the container.

```php
if( $container->has(SomeClass::class) ){
	echo "Container has SomeClass::class.";
}
```

## Advanced usage

### Singleton builder

The singleton builder will ensure only one single instance is ever returned.

It also has the added benefit over the ```set``` method of lazily instantiating the class. I.e. it will only be created when it is actually needed.

```php
$container->singleton(
	SomeClass::class,
	function(Container $container): void {
		return new SomeClass(
			$container->get('SomeDependency')
		);
	}
);
```

### Factory builder

The factory builder will create new instances each time it is called.

```php
$container->factory(
	SomeClass::class,
	function(Container $container): void {
		return new SomeClass(
			$container->get('SomeDependency')
		);
	}
);
```
### Registering services

Service providers allow you to organize your application dependecies in an OOO fashion.

Create service classes that implement ```ServiceProviderInterface```.

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

Then register your services providers with the container.

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