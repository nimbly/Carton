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
	 * Additional containers.
	 *
	 * @var array<ContainerInterface>
	 */
	protected array $containers = [];

	/**
	 * Container items.
	 *
	 * @var array<array-key,BuilderInterface>
	 */
	protected array $items = [];


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
	 * Add a container.
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

		throw new NotFoundException("Container item \"{$id}\" not found.");
	}

	/**
	 * Sets instance to container.
	 *
	 * @param string $id
	 * @param mixed $item
	 * @return void
	 */
	public function set(string $id, mixed $item): void
	{
		if( $item instanceof BuilderInterface === false ){
			$item = new ConcreteBuilder($item);
		}

		$this->items[$id] = $item;
	}

	/**
	 * Set a singleton builder.
	 *
	 * @param string $id
	 * @param callable $builder
	 * @return void
	 */
	public function singleton(string $id, callable $builder): void
	{
		$this->set(
			$id,
			new SingletonBuilder($builder)
		);
	}

	/**
	 * Set a factory builder.
	 *
	 * @param string $id
	 * @param callable $builder
	 * @return void
	 */
	public function factory(string $id, callable $builder): void
	{
		$this->set(
			$id,
			new FactoryBuilder($builder)
		);
	}

	/**
	 * Alias an item to another item.
	 *
	 * @param string $alias
	 * @param string $id
	 * @return void
	 */
	public function alias(string $alias, string $id): void
	{
		if( !\array_key_exists($id, $this->items) ){
			throw new NotFoundException("Container item {$id} not found.");
		}

		$this->set(
			$alias,
			$this->items[$id]
		);
	}

	/**
	 * Register a set of service providers.
	 *
	 * @param array<ServiceProviderInterface|class-string> $serviceProviders
	 * @return void
	 */
	public function register(array $serviceProviders): void
	{
		foreach( $serviceProviders as $serviceProviderClass ){
			if( \is_string($serviceProviderClass) ){
				$serviceProviderClass = $this->make($serviceProviderClass);
			}

			if( $serviceProviderClass instanceof ServiceProviderInterface === false ){
				throw new ContainerException("Service provider not instance of ServiceProviderInterface");
			}

			$serviceProviderClass->register($this);
		}
	}

	/**
	 * Call a callable with values from container and optional parameters.
	 *
	 * @param callable $callable
	 * @param array<array-key,mixed> $parameters
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
	 * @param string $class_name
	 * @param array<array-key,mixed> $parameters
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
	 * @param string|callable $callable
	 * @param array<array-key,mixed> $parameters
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