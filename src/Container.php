<?php

namespace Carton;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionParameter;

class Container implements ContainerInterface
{
	/**
	 * The container singleton instance.
	 *
	 * @var Container|null
	 */
	protected static $instance;

	/**
	 * Additional containers.
	 *
	 * @var array<ContainerInterface>
	 */
	protected $containers = [];

	/**
	 * Container items.
	 *
	 * @var array<string, BuilderInterface>
	 */
	protected $items = [];

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
	public function has($id)
	{
		// Try the root container first.
		if( \array_key_exists($id, $this->items) ){
			return true;
		}

		// Loop through additional containers.
		/** @var ContainerInterface $container */
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
	public function get($id)
	{
		// Try the root container first.
		if( \array_key_exists($id, $this->items) ){
			return $this->items[$id]->build($this);
		}

		/** @var ContainerInterface $container */
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
	public function set(string $id, $item): void
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
	 * Make an instance of the given class.
	 *
	 * Parameters is a key => value pair of parameters that will be injected into
	 * the constructor if they cannot be resolved.
	 *
	 * @param string $className
	 * @param array<string, mixed> $parameters
	 * @return object
	 */
	public function make(string $className, array $parameters = []): object
	{
		// Do some reflection to determine constructor parameters
		/** @psalm-suppress ArgumentTypeCoercion */
		$reflectionClass = new ReflectionClass($className);

		$constructor = $reflectionClass->getConstructor();

		if( empty($constructor) ){
			/** @psalm-suppress InvalidStringClass */
			return new $className;
		}

		$args = $this->resolveParameters(
			$constructor->getParameters(),
			$parameters
		);

		/** @psalm-suppress InvalidStringClass */
		return new $className(...$args);
	}

	/**
	 * Call a method on a class.
	 *
	 * @param string|object $class
	 * @param string $method
	 * @param array<string, mixed> $parameters
	 * @return mixed
	 */
	public function call($class, string $method, array $parameters = [])
	{
		/** @psalm-suppress ArgumentTypeCoercion */
		$reflectionClass = new ReflectionClass($class);
		$reflectionMethod = $reflectionClass->getMethod($method);

		$args = $this->resolveParameters(
			$reflectionMethod->getParameters(),
			$parameters
		);

		if( !\is_object($class) ){
			$class = $this->make($class);
		}

		return \call_user_func_array(
			[$class, $method],
			$args
		);
	}

	/**
	 * Resolve parameters.
	 *
	 * @param array<ReflectionParameter> $reflectionParameters
	 * @param array<string, mixed> $parameters
	 * @return array
	 */
	protected function resolveParameters(array $reflectionParameters, array $parameters = []): array
	{
		return \array_map(function(ReflectionParameter $reflectionParameter) use ($parameters) {

			// Is this a user supplied argument?
			if( \array_key_exists($reflectionParameter->getName(), $parameters) ){
				return $parameters[$reflectionParameter->getName()];
			}

			// Parameter type
			/** @psalm-suppress PossiblyNullReference */
			elseif( $reflectionParameter->hasType() &&
					$reflectionParameter->getType()->isBuiltin() === false ){

				// Check container
				if( $this->has((string) $reflectionParameter->getType()) ){
					return $this->get((string) $reflectionParameter->getType());
				}

				// Try to make it
				else {
					return $this->make((string) $reflectionParameter->getType());
				}
			}

			// Is there a default value provided? Use that.
			elseif( $reflectionParameter->isDefaultValueAvailable() ){
				return $reflectionParameter->getDefaultValue();
			}

			// Is this option nullable?
			elseif( $reflectionParameter->isOptional() ){
				return null;
			}

			throw new ContainerException("Cannot resolve parameter {$reflectionParameter->getName()}.");

		}, $reflectionParameters);
	}

	/**
	 * Register a set of service providers.
	 *
	 * @param ServiceProviderInterface|array<ServiceProviderInterface|string> $serviceProvider
	 * @return void
	 */
	public function register($serviceProvider): void
	{
		if( !\is_array($serviceProvider) ){
			$serviceProvider = [$serviceProvider];
		}

		foreach( $serviceProvider as $serviceProviderClass ){
			if( \is_string($serviceProviderClass) && \class_exists($serviceProviderClass) ){
				$serviceProviderClass = new $serviceProviderClass;
			}

			if( $serviceProviderClass instanceof ServiceProviderInterface === false ){
				throw new ContainerException("Service provider not instance of ServiceProviderInterface");
			}

			$serviceProviderClass->register($this);
		}
	}
}