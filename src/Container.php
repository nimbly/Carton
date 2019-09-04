<?php

namespace Carton;

use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
	/**
	 * The container singleton instance.
	 *
	 * @var Container
	 */
	protected static $instance;

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
	 * @inheritDoc
	 */
	public function has($id)
	{
		return \array_key_exists($id, $this->items);
	}

	/**
	 * @inheritDoc
	 */
	public function get($id)
	{
		if( $this->has($id) === false ){
			throw new ContainerNotFoundException("Container item not found.");
		}

		return $this->items[$id]->build($this);
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
		if( $this->has($id) ){
			throw new ContainerException("Container already has item \"{$id}\".");
		}

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
}