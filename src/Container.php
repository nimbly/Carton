<?php

namespace Carton;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionObject;
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
	 * @var array<string,BuilderInterface>
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
	 * Make an instance of the given class.
	 *
	 * Parameters is a key => value pair of parameters that will be injected into
	 * the constructor if they cannot be resolved.
	 *
	 * @param class-string $className
	 * @param array<string,mixed> $parameters
	 * @return object
	 */
	public function make(string $className, array $parameters = []): object
	{
		// Do some reflection to determine constructor parameters
		$reflectionClass = new ReflectionClass($className);

		$constructor = $reflectionClass->getConstructor();

		if( empty($constructor) ){
			return $reflectionClass->newInstance();
		}

		$args = $this->resolveReflectionParameters(
			$constructor->getParameters(),
			$parameters
		);

		return $reflectionClass->newInstanceArgs($args);
	}

	/**
	 * Call a callable with optional given parameters.
	 *
	 * @param callable $callable
	 * @param array<string,mixed> $parameters
	 * @throws CallableResolutionException
	 * @return mixed
	 */
	public function call(callable $callable, array $parameters = [])
	{
		return \call_user_func_array(
			$callable,
			$this->getCallableArguments($callable, $parameters)
		);
	}

	/**
	 * Try to make something callable.
	 *
	 * Supports:
	 *  - Fully\Qualified\Namespace\Classname@Method
	 *  - Fully\Qualified\Namespace\Classname (if class has __invoke() method.)
	 *
	 * @param string|callable $callable
	 * @param array<string,mixed> $parameters
	 * @return callable
	 */
	public function makeCallable($callable, array $parameters = []): callable
	{
		if( \is_string($callable) ) {

			if( \class_exists($callable) ){
				$callable = $this->make($callable, $parameters);
			}
			elseif( \preg_match("/^(.+)@(.+)$/", $callable, $match) ){

				/** @psalm-suppress ArgumentTypeCoercion */
				$callable = [
					$this->make($match[1], $parameters),
					$match[2]
				];
			}
		}

		if( \is_callable($callable) ){
			return $callable;
		}

		throw new CallableResolutionException("Cannot make callable");
	}

	/**
	 * Given a callable, get its arguments resolved using the container and optionally any
	 * user supplied parameters.
	 *
	 * @param callable $callable
	 * @param array<string,mixed> $parameters
	 * @return array<mixed>
	 */
	public function getCallableArguments(callable $callable, array $parameters = []): array
	{
		if( \is_array($callable) ){
			[$class, $method] = $callable;

			/** @psalm-suppress ArgumentTypeCoercion */
			$reflectionClass = new ReflectionClass($class);
			$reflectionMethod = $reflectionClass->getMethod($method);
			$reflectionParameters = $reflectionMethod->getParameters();
		}

		elseif( \is_object($callable) && \method_exists($callable, "__invoke")) {

			$reflectionObject = new ReflectionObject($callable);
			$reflectionMethod = $reflectionObject->getMethod("__invoke");
			$reflectionParameters = $reflectionMethod->getParameters();
		}

		elseif( \is_string($callable)) {
			$reflectionFunction = new ReflectionFunction($callable);
			$reflectionParameters = $reflectionFunction->getParameters();
		}

		else {
			throw new CallableResolutionException("No support for this type of callable.");
		}

		return $this->resolveReflectionParameters(
			$reflectionParameters,
			$parameters
		);
	}

	/**
	 * Resolve parameters.
	 *
	 * @param array<ReflectionParameter> $reflectionParameters
	 * @param array<string,mixed> $parameters
	 * @throws ParameterResolutionException
	 * @return array<mixed>
	 */
	protected function resolveReflectionParameters(array $reflectionParameters, array $parameters = []): array
	{
		return \array_map(
			/**
			 * @return mixed
			 */
			function(ReflectionParameter $reflectionParameter) use ($parameters) {

				$parameterName = $reflectionParameter->getName();
				$parameterType = $reflectionParameter->getType();

				// Check parameters for a match by name.
				if( \array_key_exists($parameterName, $parameters) ){
					return $parameters[$parameterName];
				}

				// Check container and parameters for a match by type.
				if( $parameterType && !$parameterType->isBuiltin() ) {

					if( $this->has($parameterType->getName()) ){
						return $this->get($parameterType->getName());
					}

					// Try to find in the parameters supplied
					$match = \array_filter(
						$parameters,
						function($parameter) use ($parameterType) {
							$parameter_type_name = $parameterType->getName();
							return $parameter instanceof $parameter_type_name;
						}
					);

					if( $match ){
						return $match[
							\array_keys($match)[0]
						];
					}

					/**
					 * @psalm-suppress ArgumentTypeCoercion
					 */
					return $this->make($parameterType->getName(), $parameters);
				}

				// No type or the type is a primitive (built in)
				if( empty($parameterType) || $parameterType->isBuiltin() ){

					// Does parameter offer a default value?
					if( $reflectionParameter->isDefaultValueAvailable() ){
						return $reflectionParameter->getDefaultValue();
					}

					elseif( $reflectionParameter->allowsNull() ){
						return null;
					}
				}

				throw new ParameterResolutionException("Cannot resolve parameter \"{$parameterName}\".");
			},
			$reflectionParameters
		);
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