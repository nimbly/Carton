<?php

namespace Nimbly\Carton;

use Nimbly\Resolve\CallableResolutionException;
use Nimbly\Resolve\ClassResolutionException;
use Nimbly\Resolve\ParameterResolutionException;
use Nimbly\Resolve\Resolve;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
	use Resolve {
		make as resolveMake;
		call as resolveCall;
		makeCallable as resolveMakeCallable;
	}

	/**
	 * The container singleton instance.
	 *
	 * @var Container|null
	 */
	protected static ?Container $instance;

	/**
	 * @param array<string,BuilderInterface> $items
	 * @param array<ContainerInterface> $containers
	 */
	public function __construct(
		protected array $items = [],
		protected array $containers = [])
	{
	}


	/**
	 * Get singleton Container instance.
	 *
	 * @return Container
	 */
	public static function getInstance(): Container
	{
		if( empty(self::$instance) ){
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Add a PSR-11 container to the pool.
	 *
	 * @param ContainerInterface $container
	 * @return void
	 */
	public function addContainer(ContainerInterface $container): void
	{
		$this->containers[] = $container;
	}

	/**
	 * @inheritDoc
	 */
	public function has(string $id): bool
	{
		// Try the root container first.
		if( \array_key_exists($id, $this->items) ){
			return true;
		}

		// Loop through additional containers.
		foreach( $this->containers as $container ){
			if( $container->has($id) ){
				return true;
			}
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function get(string $id): mixed
	{
		// Try the root container first.
		if( \array_key_exists($id, $this->items) ){
			return $this->items[$id]->build($this);
		}

		foreach( $this->containers as $container ){
			if( $container->has($id) ){
				return $container->get($id);
			}
		}

		throw new NotFoundException(
			\sprintf(
				"Container item %s not found.",
				$id
			)
		);
	}

	/**
	 * Sets instance to container.
	 *
	 * This will return the *exact* item you provide with each call to the container.
	 *
	 * @param string $id The item ID or name. If the ID already exists in the container, it will be overwritten with the new item!
	 * @param mixed $item The item instance or value. Can be anything you want (string, object, resource, etc.)
	 * @param string|array<string> $aliases An alias or an array of aliases for this container item.
	 * @return void
	 */
	public function set(string $id, mixed $item, string|array $aliases = []): void
	{
		if( $item instanceof BuilderInterface === false ){
			$item = new ConcreteBuilder($item);
		}

		$this->items[$id] = $item;

		if( $aliases ){
			$this->alias($aliases, $id);
		}
	}

	/**
	 * Set a singleton builder.
	 *
	 * A singleton builder will return the same instance with each call to the container.
	 *
	 * @param string $id The item ID or name. If the ID already exists in the container, it will be overwritten with the new item!
	 * @param callable $builder A callable that will build the item. This callable will only be executed when the item is retrieved from the container.
	 * @param string|array<string> $aliases An alias or an array of aliases for this container item.
	 * @return void
	 */
	public function singleton(string $id, callable $builder, string|array $aliases = []): void
	{
		$this->set($id, new SingletonBuilder($builder), $aliases);
	}

	/**
	 * Set a factory builder.
	 *
	 * A factory builder will return a new instance with each call to the container.
	 *
	 * @param string $id The item ID or name. If the ID already exists in the container, it will be overwritten with the new item!
	 * @param callable $builder A callable that will build the item. This callable will only be executed when the item is retrieved from the container.
	 * @param string|array<string> $aliases An alias or an array of aliases for this container item.
	 * @return void
	 */
	public function factory(string $id, callable $builder, string|array $aliases = []): void
	{
		$this->set($id, new FactoryBuilder($builder), $aliases);
	}

	/**
	 * Alias a key (or an array of keys) to another container item.
	 *
	 * @param string|array<string> $alias A name or an array of names to use as aliases.
	 * @param string $id An existing container item you would like the alias(es) to point to.
	 * @throws NotFoundException
	 * @return void
	 */
	public function alias(string|array $alias, string $id): void
	{
		if( $this->has($id) === false ){
			throw new NotFoundException(
				\sprintf("Cannot alias %s: container item not found.", $id)
			);
		}

		if( \is_string($alias) ){
			$alias = [$alias];
		}

		foreach( $alias as $a ){
			$this->set($a, $this->items[$id]);
		}
	}

	/**
	 * Register a set of service providers.
	 *
	 * @param array<ServiceProviderInterface|class-string> $serviceProviders An array of `ServiceProviderInterface` instances or fully qualified class names that implement `ServiceProviderInterface`.
	 * @throws ContainerException
	 * @throws ClassResolutionException
	 * @throws ParameterResolutionException
	 * @return void
	 */
	public function register(array $serviceProviders): void
	{
		foreach( $serviceProviders as $serviceProviderClass ){
			if( \is_string($serviceProviderClass) ){
				$serviceProviderClass = $this->make($serviceProviderClass);
			}

			if( $serviceProviderClass instanceof ServiceProviderInterface === false ){
				throw new ContainerException(
					\sprintf(
						"Service provider %s not instance of ServiceProviderInterface.",
						$serviceProviderClass::class
					)
				);
			}

			$serviceProviderClass->register($this);
		}
	}

	/**
	 * Call a callable with values from container and optional parameters.
	 *
	 * @param callable $callable The callable to call/invoke.
	 * @param array<string,mixed> $parameters Additional parameters to be used when resolving dependencies.
	 * @throws ParameterResolutionException
	 * @return mixed
	 */
	public function call(callable $callable, array $parameters = []): mixed
	{
		return $this->resolveCall(
			$callable,
			$this,
			$parameters
		);
	}

	/**
	 * Make an instance of a class with the given fully qualified class name.
	 *
	 * @param string $class_name A fully qualified class name (including name space.)
	 * @param array<string,mixed> $parameters Additional parameters to be used when resolving dependencies.
	 * @throws ParameterResolutionException
	 * @throws ClassResolutionException
	 * @return object
	 */
	public function make(string $class_name, array $parameters = []): object
	{
		return $this->resolveMake(
			$class_name,
			$this,
			$parameters
		);
	}

	/**
	 * Make something callable.
	 *
	 * @param string|callable $callable A string in the format `Fully\Qualified\Namespace\Class@methodName` or an actual callable.
	 * @param array<string,mixed> $parameters  Additional parameters to be used when resolving dependencies.
	 * @throws ParameterResolutionException
	 * @throws ClassResolutionException
	 * @throws CallableResolutionException
	 * @return callable
	 */
	public function makeCallable(string|callable $callable, array $parameters = []): callable
	{
		return $this->resolveMakeCallable(
			$callable,
			$this,
			$parameters
		);
	}
}